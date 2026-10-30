import pathlib,re,subprocess,json
root=pathlib.Path('/home/oeffe/PhpstormProjects/websign/sillbeam-core'); output=root/'docs/development/final-release-acceptance-2026-10-30/agent-docs'
files=[root/f for f in ('README.md','CHANGELOG.md','CONTRIBUTING.md','SUPPORT.md','SECURITY.md')]+[p for p in (root/'docs').rglob('*.md') if 'development' not in p.relative_to(root/'docs').parts and 'superpowers' not in p.relative_to(root/'docs').parts]
refs={}
for file in files:
 for rev,path in re.findall(r'https://github\.com/WebSign-Dev/sillbeam-core/blob/([a-f0-9]{40})/([^\s)"#>]+)',file.read_text()):
  ref=rev+':'+path
  p=subprocess.run(['git','cat-file','-e',ref],cwd=root,capture_output=True,text=True)
  refs[ref]={'exists_in_local_git':p.returncode==0,'exit_code':p.returncode}
result={'unique_immutable_repository_targets':len(refs),'broken':[ref for ref,check in refs.items() if not check['exists_in_local_git']],'targets':refs,'limit':'Validates immutable repository targets against local Git objects; does not claim HTTP/remote publication availability.'}
(output/'immutable-repository-links.json').write_text(json.dumps(result,indent=2)+'\n'); print(json.dumps(result,indent=2))
