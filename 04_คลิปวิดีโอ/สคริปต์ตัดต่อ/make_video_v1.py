# -*- coding: utf-8 -*-
"""ตัดต่อคลิปโปรโมทร้านจิรวัสสังฆภัณฑ์ (วิชา 1101023)
ภาพ: คลิปบรรยากาศร้าน (01_ไฟล์ดิบ_RAW/*.MOV)  เสียง: บทสัมภาษณ์เจ้าของร้าน (Chirawat_Monk_Supply.m4a)
ต้องมี ffmpeg/ffprobe ใน PATH   วิธีใช้: python make_video.py
"""
import subprocess, pathlib, json, shutil, sys

ROOT = pathlib.Path(__file__).resolve().parent.parent.parent
RAW = ROOT / "02_ไฟล์ดิบ_RAW" / "คลิปวิดีโอ_MOV"
OUT = ROOT / "04_คลิปวิดีโอ" / "ไฟล์ส่ง"
CARDS = ROOT / "04_คลิปวิดีโอ" / "กราฟิก"
import tempfile
WORK = pathlib.Path(tempfile.gettempdir()) / "jirawat_video_work"
WORK.mkdir(exist_ok=True)
INTERVIEW = ROOT / "02_ไฟล์ดิบ_RAW" / "เสียงสัมภาษณ์" / "Chirawat_Monk_Supply.m4a"
INTRO, OUTRO = 5.0, 7.0

# (ไฟล์, วินาทีเริ่ม, ความยาว) เรียงตามลำดับเรื่อง: หน้าร้าน > เดินชมร้าน > สินค้าแต่ละหมวด > บ่อปลา > ปิดท้าย
SEGMENTS = [
    ("IMG_3036", 5.0, 4.3), ("IMG_3030", 1.5, 6.0), ("IMG_3035", 3.5, 5.0), ("IMG_3034", 0.5, 4.5),
    ("IMG_3037", 0.0, 4.0), ("IMG_3038", 0.0, 5.0), ("IMG_3030", 20.0, 5.0), ("IMG_3037", 4.0, 3.9),
    ("IMG_3043", 0.0, 5.0), ("IMG_3032", 3.0, 5.0), ("IMG_3031", 0.0, 3.7), ("IMG_3033", 1.0, 5.0),
    ("IMG_3039", 0.0, 5.0), ("IMG_3030", 45.0, 5.0), ("IMG_3040", 0.0, 4.5), ("IMG_3039", 6.0, 5.0),
    ("IMG_3044", 10.0, 5.0), ("IMG_3041", 0.0, 4.5), ("IMG_3032", 18.0, 5.0), ("IMG_3040", 5.0, 4.0),
    ("IMG_3043", 8.0, 5.0), ("IMG_3030", 70.0, 5.0), ("IMG_3033", 10.0, 5.0), ("IMG_3044", 40.0, 5.0),
    ("IMG_3041", 4.5, 4.0), ("IMG_3032", 35.0, 5.0), ("IMG_3030", 88.0, 5.0), ("IMG_3044", 70.0, 5.0),
    ("IMG_3042", 2.0, 5.0), ("IMG_3030", 32.0, 5.0), ("IMG_3044", 55.0, 5.0), ("IMG_3032", 26.0, 5.0),
    ("IMG_3030", 58.0, 5.0), ("IMG_3044", 85.0, 5.0), ("IMG_3042", 10.0, 5.0), ("IMG_3043", 12.5, 5.0),
]

FPS = 30
H264 = ["-c:v", "libx264", "-preset", "medium", "-crf", "20", "-pix_fmt", "yuv420p", "-r", str(FPS)]


def run(cmd):
    print(" ".join(str(c) for c in cmd)[:160])
    subprocess.run(cmd, check=True)


def probe(p, entry):
    r = subprocess.run(["ffprobe", "-v", "error", "-show_entries", entry, "-of", "json", str(p)], capture_output=True, text=True)
    return json.loads(r.stdout)


def duration(p):
    return float(probe(p, "format=duration")["format"]["duration"])


def is_vertical(p):
    s = probe(p, "stream=width,height:stream_side_data=rotation")["streams"][0]
    rot = abs(int(s.get("side_data_list", [{}])[0].get("rotation", 0) or 0))
    w, h = s["width"], s["height"]
    return (h > w) != (rot == 90)


audio_len = duration(INTERVIEW)
total = INTRO + audio_len + OUTRO
print(f"interview {audio_len:.1f}s -> video {total:.1f}s")

# 1) ตัดแต่ละช่วงเป็น 1920x1080 (คลิปแนวตั้งทำพื้นหลังเบลอ)
parts, acc = [], 0.0
for i, (name, ss, dur) in enumerate(SEGMENTS):
    if acc >= total:
        break
    dur = min(dur, total - acc)
    src = RAW / f"{name}.MOV"
    dst = WORK / f"seg_{i:02d}.mp4"
    if is_vertical(src):
        vf = ("split[a][b];[a]scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080,boxblur=30:5,eq=brightness=-0.08[bg];"
              "[b]scale=-2:1080[fg];[bg][fg]overlay=(W-w)/2:0")
    else:
        vf = "scale=1920:1080:force_original_aspect_ratio=increase,crop=1920:1080"
    vf += f",fps={FPS},eq=saturation=1.08:contrast=1.03,format=yuv420p"
    if not dst.exists():
        run(["ffmpeg", "-v", "error", "-y", "-ss", f"{ss}", "-t", f"{dur}", "-i", src, "-filter_complex", vf,
             "-an", *H264, dst])
    parts.append(dst)
    acc += dur
if acc < total - 0.5:
    sys.exit(f"ภาพไม่พอ: ได้ {acc:.1f}s ต้องการ {total:.1f}s เพิ่ม SEGMENTS")

(WORK / "list.txt").write_text("".join(f"file '{p.name}'\n" for p in parts), encoding="utf-8")
broll = WORK / "broll.mp4"
run(["ffmpeg", "-v", "error", "-y", "-f", "concat", "-safe", "0", "-i", WORK / "list.txt", "-c", "copy", broll])

# 2) ซ้อนไตเติล + ใส่เสียงสัมภาษณ์ + เฟดเข้า/ออก
final = OUT / "คลิปโปรโมท_ร้านจิรวัสสังฆภัณฑ์.mp4"
lower_on, lower_off = INTRO + 1, INTRO + 9
end_on = INTRO + audio_len
fc = (
    f"[0:v][1:v]overlay=0:0:enable='lt(t,{INTRO})'[v1];"
    f"[v1][2:v]overlay=0:0:enable='between(t,{lower_on},{lower_off})'[v2];"
    f"[v2][3:v]overlay=0:0:enable='gte(t,{end_on})',fade=t=in:st=0:d=1,fade=t=out:st={total-1.2}:d=1.2[v];"
    f"[4:a]adelay={int(INTRO*1000)}|{int(INTRO*1000)},loudnorm=I=-16:TP=-1.5:LRA=11,apad,atrim=0:{total},"
    f"afade=t=out:st={total-1.5}:d=1.5[a]"
)
run(["ffmpeg", "-v", "error", "-y", "-i", broll,
     "-loop", "1", "-i", CARDS / "title.png", "-loop", "1", "-i", CARDS / "lower.png", "-loop", "1", "-i", CARDS / "end.png",
     "-i", INTERVIEW, "-filter_complex", fc, "-map", "[v]", "-map", "[a]", "-t", f"{total}",
     *H264, "-c:a", "aac", "-b:a", "192k", "-movflags", "+faststart", final])

# 3) ไฟล์เล็กสำหรับขึ้นเว็บ + ภาพปก
web = OUT / "promo_web_720p.mp4"
run(["ffmpeg", "-v", "error", "-y", "-i", final, "-vf", "scale=1280:720", "-c:v", "libx264", "-preset", "medium", "-crf", "24",
     "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "128k", "-movflags", "+faststart", web])
poster = OUT / "poster.jpg"
run(["ffmpeg", "-v", "error", "-y", "-ss", "2.5", "-i", final, "-frames:v", "1", "-vf", "scale=1280:720", poster])
print("DONE", final, f"{duration(final):.1f}s")
