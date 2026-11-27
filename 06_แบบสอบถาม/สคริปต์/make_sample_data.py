# -*- coding: utf-8 -*-
"""สร้าง "ข้อมูลจำลอง" (SIMULATED) สำหรับทดสอบหน้าตา dashboard เท่านั้น
ไม่ใช่คำตอบจากลูกค้าจริง เมื่อได้ผลแบบสอบถามจริงให้แทนที่ไฟล์ survey.csv
"""
import csv, random, sys, pathlib
sys.path.insert(0, str(pathlib.Path(__file__).parent))
from questions import PART1, PART2, PART3, columns

random.seed(2569)
N = 110
W = {
    "age": [5, 18, 20, 22, 20, 15], "occupation": [10, 18, 20, 25, 10, 15, 2],
    "income": [15, 30, 25, 20, 10], "area": [62, 30, 8],
    "freq": [8, 30, 38, 24], "occasion": [30, 22, 14, 10, 12, 10, 2],
    "product": [18, 22, 35, 10, 13, 2], "spend": [20, 32, 28, 15, 5],
    "time": [45, 25, 18, 12], "with": [30, 45, 12, 13], "know": [45, 30, 12, 10, 3],
    "purpose": [55, 25, 10, 5, 5], "pay": [48, 47, 5], "factor": [30, 20, 22, 13, 15],
    "online": [35, 45, 20], "repeat": [70, 25, 5],
}
BASE = {"prod": 4.35, "price": 4.05, "staff": 4.5, "place": 3.85, "channel": 3.6}
SUGG = ["", "", "", "", "อยากให้มีที่จอดรถมากกว่านี้", "อยากให้เปิดสั่งของทาง LINE", "พนักงานบริการดีมาก",
        "อยากให้มีโปรโมชันช่วงเข้าพรรษา", "สินค้าครบดี", "อยากให้ลง Facebook บ่อยขึ้น", "ราคาเหมาะสม",
        "อยากให้มีบริการส่งถึงวัด", "จัดของเป็นระเบียบขึ้นอีกนิดจะดีมาก"]


def likert(mu):
    return max(1, min(5, round(random.gauss(mu, 0.75))))


rows = []
for i in range(N):
    r = {"id": i + 1, "gender": "ชาย" if i % 2 == 0 else "หญิง"}
    for k, _, opts in PART1[1:] + PART2:
        r[k] = random.choices(opts, W[k])[0]
    tot = 0
    for k, _, items in PART3:
        p = BASE[k] + random.gauss(0, 0.3)
        vals = [likert(p) for _ in items]
        for j, v in enumerate(vals):
            r[f"{k}_{j+1}"] = v
        r[f"{k}_overall"] = max(1, min(5, round(sum(vals) / len(vals) + random.gauss(0, 0.3))))
        tot += r[f"{k}_overall"]
    order = sorted(BASE, key=lambda k: -(BASE[k] + random.gauss(0, 0.35)))
    for rank, k in enumerate(order, 1):
        r[f"rank_{k}"] = rank
    r["overall"] = max(1, min(5, round(tot / 5 + random.gauss(0.1, 0.3))))
    r["suggestion"] = random.choice(SUGG)
    rows.append(r)

out = pathlib.Path(__file__).parent.parent / "ข้อมูล" / "survey_SIMULATED.csv"
with open(out, "w", newline="", encoding="utf-8-sig") as f:
    w = csv.DictWriter(f, fieldnames=columns())
    w.writeheader(); w.writerows(rows)
print("wrote", out, len(rows))
