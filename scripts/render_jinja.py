import json, os, sys
from jinja2 import Environment, FileSystemLoader, StrictUndefined

if len(sys.argv) != 4:
    print("Usage: render_jinja.py <template_path> <data_json_path> <out_md_path>", file=sys.stderr)
    sys.exit(2)

template_path, data_path, out_path = sys.argv[1], sys.argv[2], sys.argv[3]

env = Environment(
    loader=FileSystemLoader(os.path.dirname(template_path)),
    undefined=StrictUndefined,

    trim_blocks=False,
    lstrip_blocks=False,
    keep_trailing_newline=True,
)

tpl = env.get_template(os.path.basename(template_path))

with open(data_path, "r", encoding="utf-8") as f:
    data = json.load(f)

rendered = tpl.render(**data)

with open(out_path, "w", encoding="utf-8", newline="\n") as f:
    f.write(rendered)