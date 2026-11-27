# -*- coding: utf-8 -*-
"""คลิปโปรโมท เวอร์ชัน 2 (ตัดต่อแบบสมูท)
- ทุกช็อตสโลว์ลง 1.2 เท่า ลดอาการภาพสั่นจากการถือกล้อง
- ซูมเข้า/ออกช้า ๆ แบบ Ken Burns (ทำจากภาพ 4K จึงไม่แตก)
- ต่อภาพแบบเฟดนุ่ม (crossfade 0.8 วินาที) ทุกช็อต
- ไตเติลเปิด, ป้ายหัวข้อแต่ละช่วง, ป้าย "เสียงจากเจ้าของร้าน" และการ์ดปิด เฟด+เลื่อนเข้าแบบแอนิเมชัน
เสียง: บทสัมภาษณ์เจ้าของร้าน (01_ไฟล์ดิบ_RAW/Chirawat_Monk_Supply.m4a)
วิธีใช้: python make_video_v2.py   (ต้องมี ffmpeg/ffprobe ใน PATH)
"""
import subprocess, pathlib, json, tempfile, sys

ROOT = pathlib.Path(__file__).resolve().parent.parent.parent
RAW = ROOT / "02_ไฟล์ดิบ_RAW" / "คลิปวิดีโอ_MOV"
OUT = ROOT / "04_คลิปวิดีโอ" / "ไฟล์ส่ง"
G = ROOT / "04_คลิปวิดีโอ" / "กราฟิก"
WORK = pathlib.Path(tempfile.gettempdir()) / "jirawat_video_v2"
WORK.mkdir(exist_ok=True)
INTERVIEW = ROOT / "02_ไฟล์ดิบ_RAW" / "เสียงสัมภาษณ์" / "Chirawat_Monk_Supply.m4a"

FPS, SLOW, XF = 30, 1.2, 0.8        # เฟรมเรต, สโลว์, ความยาวเฟดต่อภาพ
INTRO, OUTRO = 4.5, 7.0

# (หัวข้อช่วง, [(ไฟล์, เริ่ม, ยาว)])
CHAPTERS = [
    ("ch1", [("IMG_3036", 5.0, 4.3), ("IMG_3030", 1.5, 5.5), ("IMG_3035", 3.5, 5.0), ("IMG_3034", 0.5, 4.5), ("IMG_3030", 32.0, 5.0)]),
    ("ch2", [("IMG_3037", 0.0, 4.0), ("IMG_3038", 0.0, 5.0), ("IMG_3030", 20.0, 5.0), ("IMG_3037", 4.0, 3.9), ("IMG_3043", 0.0, 5.0), ("IMG_3041", 0.0, 4.5), ("IMG_3044", 10.0, 5.0)]),
    ("ch3", [("IMG_3032", 3.0, 5.0), ("IMG_3031", 0.0, 3.7), ("IMG_3033", 1.0, 5.0), ("IMG_3039", 0.0, 5.0), ("IMG_3030", 45.0, 5.0), ("IMG_3040", 0.0, 4.5), ("IMG_3039", 6.0, 5.0), ("IMG_3033", 10.0, 5.0)]),
    ("ch4", [("IMG_3043", 8.0, 5.0), ("IMG_3041", 4.5, 4.0), ("IMG_3032", 18.0, 5.0), ("IMG_3044", 40.0, 5.0), ("IMG_3030", 70.0, 5.0), ("IMG_3040", 5.0, 4.0), ("IMG_3032", 35.0, 5.0), ("IMG_3044", 70.0, 5.0), ("IMG_3030", 88.0, 5.0), ("IMG_3044", 25.0, 5.0), ("IMG_3030", 80.0, 4.5)]),
    ("ch5", [("IMG_3042", 2.0, 5.0), ("IMG_3030", 58.0, 5.0), ("IMG_3042", 10.0, 5.0), ("IMG_3043", 12.5, 5.0), ("IMG_3036", 0.5, 4.5)]),
]


def run(cmd):
    print(" ".join(str(c) for c in cmd)[:150], flush=True)
    subprocess.run([str(c) for c in cmd], check=True)


def probe(p, entry):
    r = subprocess.run(["ffprobe", "-v", "error", "-show_entries", entry, "-of", "json", str(p)], capture_output=True, text=True)
    return json.loads(r.stdout)


def duration(p):
    return float(probe(p, "format=duration")["format"]["duration"])


def is_vertical(p):
    s = probe(p, "stream=width,height:stream_side_data=rotation")["streams"][0]
    rot = abs(int(s.get("side_data_list", [{}])[0].get("rotation", 0) or 0))
    return (s["height"] > s["width"]) != (rot == 90)


audio_len = duration(INTERVIEW)
total = INTRO + audio_len + OUTRO
print(f"target {total:.1f}s")

# 1) เตรียมช็อต: สโลว์ + Ken Burns ที่ 1080p
shots, acc = [], 0.0
for ch, segs in CHAPTERS:
    for name, ss, dur in segs:
        out_len = dur * SLOW
        if shots and acc - XF >= total:
            break
        idx = len(shots)
        dst = WORK / f"s{idx:02d}_{name}_{ss}.mp4"
        frames = int(out_len * FPS)
        zin = idx % 2 == 0
        z = f"1+0.06*on/{frames}" if zin else f"1.06-0.06*on/{frames}"
        src = RAW / f"{name}.MOV"
        if is_vertical(src):
            base = ("split[a][b];[a]scale=3840:2160:force_original_aspect_ratio=increase,crop=3840:2160,boxblur=40:6,eq=brightness=-0.1[bg];"
                    "[b]scale=-2:2160[fg];[bg][fg]overlay=(W-w)/2:0")
        else:
            base = "scale=3840:2160:force_original_aspect_ratio=increase,crop=3840:2160"
        vf = (f"{base},setpts={SLOW}*PTS,fps={FPS},"
              f"zoompan=z='{z}':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d=1:s=1920x1080:fps={FPS},"
              f"eq=saturation=1.1:contrast=1.04:gamma=1.02,format=yuv420p")
        if not dst.exists():
            run(["ffmpeg", "-v", "error", "-y", "-ss", ss, "-t", dur, "-i", src, "-filter_complex", vf, "-an",
                 "-c:v", "libx264", "-preset", "fast", "-crf", "18", "-r", FPS, dst])
        d = duration(dst)
        shots.append((ch, dst, d))
        acc += d - (XF if idx else 0)
if acc < total:
    sys.exit(f"ภาพไม่พอ ({acc:.1f}s < {total:.1f}s)")

# 2) ต่อช็อตด้วย crossfade
inputs, fc, prev, offset, ch_start = [], [], "0:v", 0.0, {}
for i, (ch, p, d) in enumerate(shots):
    inputs += ["-i", p]
    if i == 0:
        offset = d
        ch_start.setdefault(ch, 0.0)
        continue
    off = offset - XF
    ch_start.setdefault(ch, off)
    fc.append(f"[{prev}][{i}:v]xfade=transition=fade:duration={XF}:offset={off:.3f}[x{i}]")
    prev = f"x{i}"
    offset = off + d
broll = WORK / "broll_v2.mp4"
run(["ffmpeg", "-v", "error", "-y", *inputs, "-filter_complex", ";".join(fc), "-map", f"[{prev}]",
     "-t", f"{total:.3f}", "-c:v", "libx264", "-preset", "fast", "-crf", "18", "-pix_fmt", "yuv420p", broll])

# 3) กราฟิกแอนิเมชัน + เสียง
cards = [("title2", 0.4, INTRO - 0.2, "c")]                       # ไตเติลเปิด
cards.append(("lower", INTRO + 0.6, INTRO + 6.6, "l"))            # เสียงจากเจ้าของร้าน
last_end = INTRO + 6.6
for ch in ["ch1", "ch2", "ch3", "ch4", "ch5"]:
    st = max(ch_start[ch] + 1.0, last_end + 1.5)
    cards.append((ch, st, st + 4.2, "l"))
    last_end = st + 4.2
end_on = INTRO + audio_len
cards.append(("end", end_on, total, "c"))

args, f = ["-i", broll], []
for k, (name, st, en, mode) in enumerate(cards, 1):
    args += ["-loop", "1", "-t", f"{total:.3f}", "-i", G / f"{name}.png"]
    fade_out = "" if name == "end" else f",fade=t=out:st={en - 0.7:.2f}:d=0.7:alpha=1"
    f.append(f"[{k}:v]format=rgba,fade=t=in:st={st:.2f}:d=0.9:alpha=1{fade_out}[g{k}]")
base = "0:v"
for k, (name, st, en, mode) in enumerate(cards, 1):
    if mode == "l":   # เลื่อนเข้าจากซ้าย
        x = f"'if(lt(t,{st:.2f}),-80,if(lt(t,{st + 0.9:.2f}),-80+80*(1-pow(1-(t-{st:.2f})/0.9,3)),0))'"
        y = "0"
    else:             # ลอยขึ้นเล็กน้อย
        x = "0"
        y = f"'if(lt(t,{st:.2f}),40,if(lt(t,{st + 1.2:.2f}),40-40*(1-pow(1-(t-{st:.2f})/1.2,3)),0))'"
    f.append(f"[{base}][g{k}]overlay=x={x}:y={y}:enable='between(t,{st:.2f},{en:.2f})'[o{k}]")
    base = f"o{k}"
f.append(f"[{base}]fade=t=in:st=0:d=1.2,fade=t=out:st={total - 1.5:.2f}:d=1.5[v]")
ai = len(cards) + 1
args += ["-i", INTERVIEW]
f.append(f"[{ai}:a]adelay={int(INTRO * 1000)}|{int(INTRO * 1000)},loudnorm=I=-16:TP=-1.5:LRA=11,"
         f"apad,atrim=0:{total:.3f},afade=t=in:st={INTRO}:d=0.4,afade=t=out:st={total - 2:.2f}:d=2[a]")

final = OUT / "คลิปโปรโมท_ร้านจิรวัสสังฆภัณฑ์_FullHD.mp4"
run(["ffmpeg", "-v", "error", "-y", *args, "-filter_complex", ";".join(f), "-map", "[v]", "-map", "[a]",
     "-t", f"{total:.3f}", "-c:v", "libx264", "-preset", "medium", "-crf", "19", "-pix_fmt", "yuv420p", "-r", FPS,
     "-c:a", "aac", "-b:a", "192k", "-movflags", "+faststart", final])
web = OUT / "คลิปโปรโมท_สำหรับเว็บ_720p.mp4"
run(["ffmpeg", "-v", "error", "-y", "-i", final, "-vf", "scale=1280:720:flags=lanczos", "-c:v", "libx264", "-preset", "medium",
     "-crf", "23", "-pix_fmt", "yuv420p", "-c:a", "aac", "-b:a", "128k", "-movflags", "+faststart", web])
run(["ffmpeg", "-v", "error", "-y", "-ss", "2.8", "-i", final, "-frames:v", "1", "-vf", "scale=1280:720", OUT / "ภาพปกคลิป.jpg"])
print("DONE", final, f"{duration(final):.1f}s", "chapters:", {k: round(v, 1) for k, v in ch_start.items()})
