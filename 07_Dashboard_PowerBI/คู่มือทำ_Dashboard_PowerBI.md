# คู่มือทำ Dashboard ใน Power BI — ร้านจิรวัสสังฆภัณฑ์ โคราช (วิชา 1101033)

> ⚠ ไฟล์ใน `data/` ตอนนี้สร้างจาก **ข้อมูลจำลอง** (`survey_SIMULATED.csv`) เพื่อจัดหน้า dashboard ให้เสร็จก่อนเท่านั้น
> เมื่อได้ผลแบบสอบถามจริงแล้ว ให้ทำตามหัวข้อ "สลับเป็นข้อมูลจริง" ด้านล่าง แล้วกด Refresh กราฟทั้งหมดจะเปลี่ยนตามเอง

## 0. ติดตั้ง Power BI Desktop (ฟรี)
เปิด PowerShell แล้ววาง:
```powershell
winget install --id Microsoft.PowerBI -e
```
หรือเปิด Microsoft Store ค้นหา "Power BI Desktop" แล้วกด Get

## 1. โหลดข้อมูล
1. เปิด Power BI Desktop > **Get data > Text/CSV**
2. เลือกไฟล์ใน `07_Dashboard_PowerBI\data\` ทีละไฟล์: `Respondents.csv`, `Satisfaction.csv`, `AspectRanking.csv`, `OptionOrder.csv`, `ScoreLevels.csv`
3. หน้าต่าง preview: File Origin = **65001: Unicode (UTF-8)** > กด **Load**

## 2. ความสัมพันธ์ (Model view)
- `Respondents[id]` 1 ── * `Satisfaction[id]`
- `Respondents[id]` 1 ── * `AspectRanking[id]`
- `ScoreLevels[score]` 1 ── * `Satisfaction[score]`
- Cross filter direction: Single (ค่าเริ่มต้น)

## 3. เรียงลำดับตัวเลือกให้ถูก (ไม่ให้เรียงตามตัวอักษร)
ใน Power Query (Transform data) ตาราง Respondents:
- ช่วงอายุ ความถี่ ค่าใช้จ่าย ฯลฯ ให้ Merge กับ `OptionOrder` (question + option) เพื่อดึง `option_order`
- หรือง่ายกว่า: ใน visual กด `...` > Sort axis > เลือกเรียงตามที่ต้องการ
- ตาราง Satisfaction: เลือกคอลัมน์ `aspect` > Column tools > **Sort by column = aspect_order**

## 4. Measures
Modeling > New measure > คัดลอกจากไฟล์ `measures.dax` ทีละอัน

## 5. หน้าตา Dashboard (แนะนำ 4 หน้า ตามส่วนที่ 1–4 ของแบบสอบถาม)
ธีม: View > Themes > เลือกโทนส้ม/ทอง (สีจีวร) เช่น `#C8781E`, `#8B4A12`, `#F3E3C3`

### หน้า 1 — ภาพรวม & ข้อมูลทั่วไป (ส่วนที่ 1)
| ตำแหน่ง | Visual | ข้อมูล |
|---|---|---|
| บนสุด | Text box ชื่อ "Dashboard ความพึงพอใจลูกค้า ร้านจิรวัสสังฆภัณฑ์ โคราช" + Card `[ป้ายแหล่งข้อมูล]` | |
| แถว KPI | Card x4 | `[จำนวนผู้ตอบ]`, `[ความพึงพอใจภาพรวมต่อร้าน]`, `[ร้อยละกลับมาซื้อซ้ำแน่นอน]`, `[ร้อยละสนใจสั่งออนไลน์]` |
| ซ้าย | Donut | Legend = gender, Values = `[จำนวนผู้ตอบ]` |
| กลาง | Clustered column | X = age, Y = `[จำนวนผู้ตอบ]`, Legend = gender |
| ขวา | Bar | Y = occupation, X = `[จำนวนผู้ตอบ]` |
| ล่าง | Bar + Pie | income, area |
| Slicer | Dropdown | gender, age (วางไว้ทุกหน้า: View > Sync slicers) |

### หน้า 2 — พฤติกรรมลูกค้า (ส่วนที่ 2)
- Bar: freq, occasion, product, spend, time (Values = `[จำนวนผู้ตอบ]`, แสดง Data label เป็น % ด้วย `[ร้อยละ]`)
- Donut: with, know, pay
- Funnel/Bar: factor (ปัจจัยที่เลือกร้าน)
- Stacked bar 100%: Y = product, Legend = age → ดูว่าแต่ละวัยซื้ออะไร
- Matrix: Rows = occasion, Columns = spend, Values = `[จำนวนผู้ตอบ]` (เปิด Conditional formatting > Background color)

### หน้า 3 — ความพึงพอใจ (ส่วนที่ 3)
- Clustered bar: Y = Satisfaction[aspect], X = `[คะแนนเฉลี่ยรายข้อ (ไม่รวมภาพรวมด้าน)]` (ตั้งแกน 1–5)
- Matrix: Rows = aspect > item, Values = `[คะแนนเฉลี่ย]`, `[ค่าเบี่ยงเบนมาตรฐาน]`, `[ระดับความพึงพอใจ]`
  (Filter: item_no ≤ 5 / Conditional formatting > Data bars)
- 100% Stacked bar: Y = aspect, X = count of id, Legend = ScoreLevels[level] → สัดส่วนคนให้ 1–5
- Column: X = AspectRanking[aspect], Y = `[จำนวนที่จัดเป็นอันดับ 1]` → ด้านที่ลูกค้าให้ความสำคัญที่สุด
- Gauge: `[ความพึงพอใจภาพรวมต่อร้าน]` Min 1, Max 5, Target 4

### หน้า 4 — ข้อเสนอแนะ (ส่วนที่ 4)
- Table: suggestion (Filter: suggestion is not blank)
- Word cloud (Visualizations > Get more visuals > "Word Cloud") จากคอลัมน์ suggestion
- Text box สรุปข้อค้นพบ 3–5 ข้อ เช่น ด้านที่ได้คะแนนต่ำสุด → ข้อเสนอแนะต่อร้าน

เกณฑ์แปลผลค่าเฉลี่ย (ใส่เป็น Text box): 4.21–5.00 มากที่สุด, 3.41–4.20 มาก, 2.61–3.40 ปานกลาง, 1.81–2.60 น้อย, 1.00–1.80 น้อยที่สุด

## 6. สลับเป็นข้อมูลจริง
1. Export ผลจาก Google Form เป็น CSV (Responses > ไอคอน Sheets > File > Download > .csv)
2. เปลี่ยนชื่อหัวคอลัมน์ให้ตรงกับ `06_แบบสอบถาม\ข้อมูล\survey_template.csv` แล้วบันทึกเป็น `06_แบบสอบถาม\ข้อมูล\survey.csv`
3. รัน:
   ```powershell
   python "06_แบบสอบถาม\สคริปต์\prep_powerbi.py" "06_แบบสอบถาม\ข้อมูล\survey.csv"
   ```
4. เปิด Power BI > Home > **Refresh** — ป้ายบนหัวจะเปลี่ยนจาก "ข้อมูลจำลอง" เป็น "ข้อมูลจากแบบสอบถามลูกค้า n = …"

## 7. นำขึ้นเว็บ (เมนู "รายงาน")
- **Publish** (ต้องใช้บัญชี Microsoft ของมหาวิทยาลัย) > เปิดรายงานใน app.powerbi.com > File > Embed report > **Publish to web** > คัดลอกโค้ด `<iframe …>`
- ใน WordPress: หน้า "รายงาน" > เพิ่มบล็อก **Custom HTML** > วาง iframe
- ถ้าบัญชีไม่อนุญาต Publish to web: Export เป็น PDF/PNG (File > Export) แล้วอัปโหลดภาพลงหน้า "รายงาน" แทน
