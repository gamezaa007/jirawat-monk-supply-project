# -*- coding: utf-8 -*-
"""แปลงไฟล์ผลแบบสอบถาม (wide CSV) เป็นตารางพร้อมใช้ใน Power BI
วิธีใช้:  python prep_powerbi.py survey.csv
ผลลัพธ์อยู่ในโฟลเดอร์ 07_Dashboard_PowerBI/data/
"""
import csv, sys, pathlib
HERE = pathlib.Path(__file__).parent
sys.path.insert(0, str(HERE))
from questions import PART1, PART2, PART3, SAT_LEVELS

src = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else HERE.parent / "ข้อมูล" / "survey_SIMULATED.csv")
out = HERE.parent.parent / "07_Dashboard_PowerBI" / "data"
out.mkdir(parents=True, exist_ok=True)
rows = list(csv.DictReader(open(src, encoding="utf-8-sig")))
is_sim = "SIMULATED" in src.name.upper()


def write(name, header, data):
    with open(out / name, "w", newline="", encoding="utf-8-sig") as f:
        w = csv.writer(f); w.writerow(header); w.writerows(data)


# 1) ผู้ตอบ (ส่วนที่ 1-2 + ภาพรวม + ข้อเสนอแนะ)
keys = [k for k, _, _ in PART1 + PART2]
write("Respondents.csv", ["id"] + keys + ["overall", "suggestion", "data_source"],
      [[r["id"]] + [r[k] for k in keys] + [r["overall"], r["suggestion"], "จำลอง" if is_sim else "จริง"] for r in rows])

# 2) คะแนนความพึงพอใจแบบยาว (1 แถว = 1 คน x 1 ข้อ)
sat = []
for r in rows:
    for ai, (k, aname, items) in enumerate(PART3, 1):
        for j, txt in enumerate(items, 1):
            sat.append([r["id"], ai, aname, j, txt, int(r[f"{k}_{j}"])])
        sat.append([r["id"], ai, aname, 6, "ภาพรวม" + aname, int(r[f"{k}_overall"])])
write("Satisfaction.csv", ["id", "aspect_order", "aspect", "item_no", "item", "score"], sat)

# 3) การจัดอันดับด้าน
write("AspectRanking.csv", ["id", "aspect", "rank"],
      [[r["id"], aname, int(r[f"rank_{k}"])] for r in rows for k, aname, _ in PART3])

# 4) ตารางอ้างอิง: ลำดับตัวเลือก (ใช้ Sort by Column ใน Power BI)
write("OptionOrder.csv", ["question", "question_label", "option", "option_order"],
      [[k, lab, o, i] for k, lab, opts in PART1 + PART2 for i, o in enumerate(opts, 1)])
write("ScoreLevels.csv", ["score", "level"], [[s, l] for s, l in sorted(SAT_LEVELS.items(), reverse=True)])
print("source:", src.name, "| respondents:", len(rows), "| out:", out)
