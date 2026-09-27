import json
import sys
import re

transcript_path = '/Users/macbook/.gemini/antigravity-ide/brain/d08cdee9-84bf-4b0a-b5e0-eba07bdbe396/.system_generated/logs/transcript_full.jsonl'

original_code = None

with open(transcript_path, 'r') as f:
    for line in f:
        try:
            data = json.loads(line)
            # Find the step where I was given the original terminal.blade.php
            # Usually in the first few messages or USER_EXPLICIT / <USER_REQUEST>
            # Let's search for the first time the file content is fully visible or pasted
            if 'content' in data:
                content = data['content']
                # The user pasted the file with "POS update change le button en card :"
                # or something similar.
                if 'terminal.blade.php' in content and '<div' in content:
                    # Let's find any large block of code
                    matches = re.findall(r'```(?:html|blade|php)?(.*?)```', content, re.DOTALL)
                    if matches:
                        for match in matches:
                            if 'class="h-screen' in match or 'x-data' in match:
                                original_code = match.strip()
                                break
                    
                    if not original_code:
                        # Try to find it if they just pasted it raw
                        if 'x-data' in content and '<div class="h-screen flex flex-col bg-slate-100' in content:
                            original_code = content
        except Exception as e:
            pass
            
        if original_code:
            break

if original_code:
    with open('/Applications/MAMP/htdocs/Yeboox_app/resources/views/livewire/pos/terminal.blade.php', 'w') as out:
        out.write(original_code)
    print("RESTORED!")
else:
    print("NOT FOUND")
