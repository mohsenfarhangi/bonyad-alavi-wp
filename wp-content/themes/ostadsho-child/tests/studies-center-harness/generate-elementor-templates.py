import json, sys, itertools, hashlib
# تولید exportهای Elementor مرکز مطالعات در wp-content/themes/ostadsho-child/elementor-templates/
# استفاده: python3 -I wp-content/themes/ostadsho-child/tests/studies-center-harness/generate-elementor-templates.py wp-content/themes/ostadsho-child/elementor-templates
out = sys.argv[1]
_c = itertools.count(1)
def eid(seed):
    return hashlib.md5(f"{seed}-{next(_c)}".encode()).hexdigest()[:7]
def px(v): return {"unit":"px","size":v,"sizes":[]}
def pad(t,r,b,l,unit="px"): return {"unit":unit,"top":str(t),"right":str(r),"bottom":str(b),"left":str(l),"isLinked":False}
def gap(v): return {"unit":"px","size":v,"column":str(v),"row":str(v),"isLinked":True}
def con(seed, settings, elements, inner=False):
    return {"id":eid(seed),"elType":"container","isInner":inner,"settings":settings,"elements":elements}
def wid(seed, wtype, settings=None):
    return {"id":eid(seed),"elType":"widget","isInner":False,"widgetType":wtype,"settings":settings or {},"elements":[]}
def save(name, title, ttype, content):
    data={"content":content,"page_settings":{"hide_title":"yes"} if ttype=="page" else [],"version":"0.4","title":title,"type":ttype}
    open(f"{out}/{name}","w",encoding="utf-8").write(json.dumps(data,ensure_ascii=False,indent=1)+"\n")

# --- صفحه داخلی نشست (Theme Builder → Single) ---
session = [con("s-root",{
    "content_width":"boxed","flex_direction":"row","flex_direction_mobile":"column","flex_align_items":"flex-start",
    "flex_gap":gap(30),"flex_gap_mobile":gap(26),
    "background_background":"classic","background_color":"#F4F4F4",
    "padding":pad(20,0,60,0),"padding_tablet":pad(14,20,40,20),"padding_mobile":pad(14,16,40,16)},[
    con("s-main",{"content_width":"full","width":{"unit":"%","size":61},"width_mobile":{"unit":"%","size":100},
        "flex_direction":"column","flex_gap":gap(22),"padding":pad(0,0,0,0)},[
        wid("s-video","bonyad_alavi_studies_session_video"),
        wid("s-content","bonyad_alavi_studies_session_content"),
    ],True),
    con("s-side",{"content_width":"full","width":{"unit":"%","size":37},"width_mobile":{"unit":"%","size":100},
        "flex_direction":"column","padding":pad(0,0,0,0)},[
        wid("s-info","bonyad_alavi_studies_session_info"),
    ],True),
])]
save("studies-session-single.json","مرکز مطالعات — صفحه داخلی نشست","single-post",session)

# --- صفحه اصلی مرکز مطالعات ---
intro_text = "<p>این مرکز به عنوان هسته تفکر و بازوی تحلیلی بنیاد علوی، راهبری فکری و پشتیبانی از تصمیم‌سازی‌های کلان بنیاد را بر عهده دارد. هدف اصلی ما این است که سرمایه‌گذاری‌های بنیاد در مناطق محروم با بیشترین اثربخشی، کارایی و پایداری به نتیجه برسد؛ از راه تحلیل دقیق چالش‌ها، طراحی راهکارهای نوآورانه و مبتنی بر شواهد، و ارزیابی مستمر نتایج.</p>"
section = lambda seed, inner, top, top_m: con(seed,{"content_width":"boxed","flex_direction":"column",
    "padding":pad(top,0,0,0),"padding_tablet":pad(top_m,20,0,20),"padding_mobile":pad(top_m,16,0,16)},inner)
page = [
    section("p-hero",[wid("p-hero-w","bonyad_alavi_studies_hero")],24,14),
    con("p-intro-wrap",{"content_width":"boxed","flex_direction":"column","padding":pad(16,0,0,0),"padding_tablet":pad(12,20,0,20),"padding_mobile":pad(12,16,0,16)},[
        con("p-intro",{"content_width":"full","flex_direction":"row","flex_direction_mobile":"column","flex_gap":gap(0),
            "flex_align_items":"stretch","overflow":"hidden",
            "border_border":"solid","border_width":pad(1,1,1,1),"border_color":"#DFE8E3",
            "border_radius":pad(18,18,18,18),"background_background":"classic","background_color":"#FFFFFF",
            "box_shadow_box_shadow_type":"yes","box_shadow_box_shadow":{"horizontal":0,"vertical":2,"blur":10,"spread":0,"color":"rgba(7,76,44,0.04)"},
            "padding":pad(0,0,0,0)},[
            con("p-intro-main",{"content_width":"full","width":{"unit":"%","size":70},"width_mobile":{"unit":"%","size":100},
                "flex_direction":"column","flex_gap":gap(10),"padding":pad(28,30,24,30),"padding_mobile":pad(22,18,16,18)},[
                wid("p-heading","bonyad_alavi_section_heading",{"layout":"stack","eyebrow":"مرکز مطالعات در یک نگاه",
                    "title":"مرکز مطالعات راهبردی و پیشرفت مناطق محروم","title_tag":"h1"}),
                wid("p-text","text-editor",{"editor":intro_text,"align":"justify","text_color":"#67766F",
                    "typography_typography":"custom","typography_font_size":px(13.5),
                    "typography_line_height":{"unit":"em","size":2.15,"sizes":[]}}),
            ],True),
            con("p-intro-side",{"content_width":"full","width":{"unit":"%","size":30},"width_mobile":{"unit":"%","size":100},
                "flex_direction":"column","flex_justify_content":"center","padding":pad(0,0,0,0),
                "background_background":"classic","background_color":"#F4F6F5"},[
                wid("p-filter","bonyad_alavi_studies_area_filter"),
            ],True),
        ],True),
    ]),
    section("p-books",[wid("p-books-w","bonyad_alavi_studies_carousel",{"content_type":"books"})],64,44),
    section("p-sessions",[wid("p-sessions-w","bonyad_alavi_studies_carousel",{"content_type":"sessions"})],64,44),
    con("p-research",{"content_width":"boxed","flex_direction":"column","padding":pad(64,0,80,0),"padding_tablet":pad(44,20,56,20),"padding_mobile":pad(44,16,56,16)},
        [wid("p-research-w","bonyad_alavi_studies_carousel",{"content_type":"research"})]),
]
save("studies-center-page.json","مرکز مطالعات — صفحه اصلی","page",page)

# --- صفحه آرشیو نشست‌ها ---
archive = [con("a-root",{"content_width":"boxed","flex_direction":"column","flex_gap":gap(24),
    "padding":pad(32,0,80,0),"padding_tablet":pad(20,20,56,20),"padding_mobile":pad(20,16,56,16)},[
    con("a-head",{"content_width":"full","flex_direction":"row","flex_direction_mobile":"column","flex_justify_content":"space-between",
        "flex_align_items":"flex-end","flex_gap":gap(20),"padding":pad(0,0,0,0)},[
        wid("a-heading","bonyad_alavi_section_heading",{"layout":"stack","eyebrow":"مرکز مطالعات","title":"نشست‌ها و رویدادهای تخصصی","title_tag":"h1"}),
        con("a-filter-box",{"content_width":"full","width":{"unit":"px","size":320},"width_mobile":{"unit":"%","size":100},
            "flex_direction":"column","overflow":"hidden","border_radius":pad(14,14,14,14),"padding":pad(0,0,0,0)},[
            wid("a-filter","bonyad_alavi_studies_area_filter",{"mode":"url","help":""}),
        ],True),
    ],True),
    wid("a-grid","bonyad_alavi_studies_grid",{"content_type":"sessions"}),
])]
save("studies-sessions-archive-page.json","مرکز مطالعات — آرشیو نشست‌ها","page",archive)
print("ok")
