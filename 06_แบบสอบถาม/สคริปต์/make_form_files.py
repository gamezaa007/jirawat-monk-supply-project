# -*- coding: utf-8 -*-
"""สร้าง (1) แบบสอบถามฉบับอ่าน .md และ (2) Google Apps Script ที่สร้าง Google Form ให้อัตโนมัติ"""
import json, sys, pathlib
HERE = pathlib.Path(__file__).parent
sys.path.insert(0, str(HERE))
from questions import PART1, PART2, PART3, SAT_LEVELS

TITLE = "แบบสำรวจความคิดเห็น ร้านจิรวัสสังฆภัณฑ์ โคราช"
DESC = ("แบบสำรวจนี้จัดทำขึ้นเพื่อศึกษาพฤติกรรมและความพึงพอใจของผู้ใช้บริการร้านจิรวัสสังฆภัณฑ์ โคราช "
        "เพื่อประกอบโครงงานรายวิชา 1101023 และ 1101033 มหาวิทยาลัยเทคโนโลยีสุรนารี "
        "ข้อมูลของท่านจะถูกเก็บเป็นความลับและนำเสนอในภาพรวมเท่านั้น")

md = [f"# {TITLE}", "", DESC, "", "## ส่วนที่ 1 : ข้อมูลทั่วไปของผู้ตอบแบบสอบถาม (ปลายปิด)", ""]
n = 0
for _, q, opts in PART1:
    n += 1; md += [f"{n}. {q}", *[f"   - ☐ {o}" for o in opts], ""]
md += ["## ส่วนที่ 2 : พฤติกรรมของลูกค้า (ปลายปิด เลือก 1 คำตอบ)", ""]
for _, q, opts in PART2:
    n += 1; md += [f"{n}. {q}", *[f"   - ☐ {o}" for o in opts], ""]
md += ["## ส่วนที่ 3 : ความพึงพอใจ/ความคิดเห็นของลูกค้า (ปลายปิด)", "",
       "ระดับ: " + "  ".join(f"{k} = {v}" for k, v in SAT_LEVELS.items()), ""]
for _, aname, items in PART3:
    md += [f"### {aname}", "", "| ข้อ | รายการ | 5 | 4 | 3 | 2 | 1 |", "|---|---|---|---|---|---|---|"]
    for it in items + [f"ภาพรวมความพึงพอใจ{aname}"]:
        n += 1; md.append(f"| {n} | {it} | ☐ | ☐ | ☐ | ☐ | ☐ |")
    md.append("")
n += 1
md += [f"{n}. โปรดจัดอันดับความสำคัญของแต่ละด้าน (1 = สำคัญที่สุด, 5 = สำคัญน้อยที่สุด)",
       *[f"   - {a} : ____" for _, a, _ in PART3], ""]
n += 1
md += [f"{n}. ภาพรวมความพึงพอใจต่อร้านจิรวัสสังฆภัณฑ์  ☐5 ☐4 ☐3 ☐2 ☐1", "",
       "## ส่วนที่ 4 : ข้อเสนอแนะ / ข้อคิดเห็นอื่น ๆ (ปลายเปิด)", "", f"{n+1}. ..............................................", "",
       "---------- ขอบคุณที่สละเวลาตอบแบบสอบถาม ----------"]
(HERE.parent / "แบบสอบถาม_ฉบับปรับปรุง.md").write_text("\n".join(md), encoding="utf-8")

spec = {
    "title": TITLE, "desc": DESC,
    "p1": [[q, o] for _, q, o in PART1], "p2": [[q, o] for _, q, o in PART2],
    "p3": [[a, items + [f"ภาพรวมความพึงพอใจ{a}"]] for _, a, items in PART3],
    "aspects": [a for _, a, _ in PART3],
}
gs = """// ===== วิธีใช้ =====
// 1) เปิด https://script.google.com > New project
// 2) ลบโค้ดเดิม แล้ววางโค้ดทั้งหมดนี้ > กด Save > กด Run (ฟังก์ชัน createForm)
// 3) อนุญาตสิทธิ์ (Review permissions > เลือกบัญชี > Allow)
// 4) ดูลิงก์ฟอร์มที่ Execution log (ลิงก์สำหรับส่งให้ลูกค้า + ลิงก์แก้ไข)
const SPEC = %s;

function createForm() {
  const f = FormApp.create(SPEC.title);
  f.setDescription(SPEC.desc).setCollectEmail(false).setProgressBar(true);
  f.addPageBreakItem().setTitle('ส่วนที่ 1 : ข้อมูลทั่วไปของผู้ตอบแบบสอบถาม');
  SPEC.p1.forEach(([q, o]) => f.addMultipleChoiceItem().setTitle(q).setChoiceValues(o).setRequired(true));
  f.addPageBreakItem().setTitle('ส่วนที่ 2 : พฤติกรรมของลูกค้า');
  SPEC.p2.forEach(([q, o]) => f.addMultipleChoiceItem().setTitle(q).setChoiceValues(o).setRequired(true));
  f.addPageBreakItem().setTitle('ส่วนที่ 3 : ความพึงพอใจของลูกค้า')
    .setHelpText('5 = พึงพอใจมากที่สุด, 4 = มาก, 3 = ปานกลาง, 2 = น้อย, 1 = น้อยที่สุด');
  SPEC.p3.forEach(([a, items]) => {
    f.addGridItem().setTitle(a).setRows(items).setColumns(['5', '4', '3', '2', '1']).setRequired(true);
  });
  f.addGridItem().setTitle('จัดอันดับความสำคัญของแต่ละด้าน (1 = สำคัญที่สุด)')
    .setRows(SPEC.aspects).setColumns(['1', '2', '3', '4', '5']).setRequired(true);
  f.addScaleItem().setTitle('ภาพรวมความพึงพอใจต่อร้านจิรวัสสังฆภัณฑ์').setBounds(1, 5)
    .setLabels('น้อยที่สุด', 'มากที่สุด').setRequired(true);
  f.addPageBreakItem().setTitle('ส่วนที่ 4 : ข้อเสนอแนะ');
  f.addParagraphTextItem().setTitle('ข้อเสนอแนะ / ข้อคิดเห็นอื่น ๆ');
  Logger.log('ลิงก์ให้ลูกค้าตอบ: ' + f.getPublishedUrl());
  Logger.log('ลิงก์แก้ไขฟอร์ม: ' + f.getEditUrl());
}
""" % json.dumps(spec, ensure_ascii=False, indent=1)
(HERE.parent / "สร้าง_GoogleForm.gs").write_text(gs, encoding="utf-8")
print("ok", n + 1, "questions")
