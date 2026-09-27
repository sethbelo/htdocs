import json
import sys

transcript_path = '/Users/macbook/.gemini/antigravity-ide/brain/d08cdee9-84bf-4b0a-b5e0-eba07bdbe396/.system_generated/logs/transcript_full.jsonl'

odoo_code = None

with open(transcript_path, 'r') as f:
    for line in f:
        try:
            data = json.loads(line)
            # Find the most recent write_to_file tool call for terminal.blade.php
            if 'tool_calls' in data:
                for tc in data['tool_calls']:
                    if tc['name'] == 'write_to_file':
                        args = tc['args']
                        if 'terminal.blade.php' in args.get('TargetFile', ''):
                            odoo_code = args.get('CodeContent', '')
        except Exception as e:
            pass

if odoo_code:
    with open('/Applications/MAMP/htdocs/Yeboox_app/resources/views/livewire/pos/terminal.blade.php', 'w') as out:
        out.write(odoo_code)
    print("RESTORED_ODOO")
else:
    print("NOT FOUND")
