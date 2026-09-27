import re

with open('resources/views/livewire/pos/terminal.blade.php', 'r') as f:
    content = f.read()

# Replace <x-icon name="NAME" class="CLASS"/> with <i data-lucide="NAME" class="CLASS"></i>
# Note: we need to handle optional class attributes and self-closing tags.

pattern = re.compile(r'<x-icon\s+name="([^"]+)"(?:\s+class="([^"]+)")?\s*(?:/>|></x-icon>)')

def replacer(match):
    name = match.group(1)
    cls = match.group(2)
    if cls:
        return f'<i data-lucide="{name}" class="{cls}"></i>'
    else:
        return f'<i data-lucide="{name}"></i>'

new_content = pattern.sub(replacer, content)

# Also catch <x-icon ...> where class is before name, or other attributes exist
pattern_all = re.compile(r'<x-icon\s+([^>]+?)\s*/?>')

def replacer_all(match):
    attrs = match.group(1)
    # Extract name
    name_match = re.search(r'name="([^"]+)"', attrs)
    if not name_match:
        return match.group(0) # don't touch if no name
    name = name_match.group(1)
    
    # Extract class
    cls_match = re.search(r'class="([^"]+)"', attrs)
    cls = cls_match.group(1) if cls_match else ""
    
    # Extract other attributes? There's stroke="2.5" in one of them: <x-icon name="plus" class="w-4 h-4" stroke="2.5"/>
    other_attrs = attrs.replace(f'name="{name}"', '').replace(f'class="{cls}"' if cls else '', '').strip()
    
    cls_attr = f' class="{cls}"' if cls else ''
    other_attr_str = f' {other_attrs}' if other_attrs else ''
    
    return f'<i data-lucide="{name}"{cls_attr}{other_attr_str}></i>'

new_content = pattern_all.sub(replacer_all, content)

with open('resources/views/livewire/pos/terminal.blade.php', 'w') as f:
    f.write(new_content)

print("Icons replaced.")
