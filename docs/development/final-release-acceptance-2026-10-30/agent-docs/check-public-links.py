import pathlib, re, json, subprocess, hashlib, urllib.parse
root=pathlib.Path('/home/oeffe/PhpstormProjects/websign/sillbeam-core'); out=root/'docs/development/final-release-acceptance-2026-10-30/agent-docs'
paths=[root/p for p in ('README.md','CHANGELOG.md','CONTRIBUTING.md','SUPPORT.md','SECURITY.md')]
paths += sorted(p for p in (root/'docs').rglob('*.md') if not any(x in p.relative_to(root/'docs').parts for x in ('development','superpowers')))
checks=[]; errors=[]
for file in paths:
 text=re.sub(r'^```[^\n]*\n.*?^```\s*$', '',file.read_text(),flags=re.M|re.S)
 targets=re.findall(r'\]\(\s*(<[^>]+>|[^\s)]+)(?:\s+"[^"]*")?\s*\)',text)+re.findall(r'^\s{0,3}\[[^]]+\]:\s*(<[^>]+>|\S+)',text,re.M)+re.findall(r'(?:href|src)\s*=\s*["\']([^"\']+)["\']',text,re.I)
 for target in targets:
  target=target.strip('<>')
  if target.startswith('#') or re.match(r'^(?:[a-z][a-z0-9+.-]*:|//)',target,re.I): continue
  path=urllib.parse.unquote(target.split('#')[0].split('?')[0]); resolved=(file.parent/path).resolve(); ok=resolved.exists()
  row={'source':str(file.relative_to(root)), 'target':target,'exists':ok}; checks.append(row)
  if not ok: errors.append(row)
workflow=(root/'.github/workflows/ci.yml').read_bytes()
metadata=json.loads((root/'composer.json').read_text())
result={'commit':subprocess.check_output(['git','rev-parse','HEAD'],cwd=root,text=True).strip(),'markdown_files':len(paths),'local_links_checked':len(checks),'broken_links':errors,'workflow_git_blob':subprocess.check_output(['git','hash-object','.github/workflows/ci.yml'],cwd=root,text=True).strip(),'workflow_sha256':hashlib.sha256(workflow).hexdigest(),'push_branches_match':b'branches: [main, dev]' in workflow,'manual_dispatch':b'workflow_dispatch:' in workflow,'branch_aliases':metadata['extra']['branch-alias'],'runtime_requirements':metadata['require'],'checks':checks,'scope':'Current packaged/public documentation only; historical development notes and fragment/remote URL crawling excluded.'}
(out/'metadata-links.json').write_text(json.dumps(result,indent=2)+'\n'); print(json.dumps({k:v for k,v in result.items() if k!='checks'},indent=2))
