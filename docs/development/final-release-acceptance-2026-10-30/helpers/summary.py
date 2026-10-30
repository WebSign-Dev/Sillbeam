import pathlib,json,hashlib,subprocess,shlex,collections,datetime,shutil
root=pathlib.Path.cwd();p=root/'docs/development/final-release-acceptance-2026-10-30'; manifest=json.loads((p/'source-sha256.json').read_text())
print(type(manifest).__name__)
if isinstance(manifest,dict) and 'files' in manifest:manifest=manifest['files']
changed=[f for f,sha in manifest.items() if not (root/f).is_file() or hashlib.sha256((root/f).read_bytes()).hexdigest()!=sha]
mat={}
for job in ['php84-sf74','php84-sf81','php85-sf74','php85-sf81']:
 wd=pathlib.Path('/tmp/sillbeam-final-matrix')/job
 mat[job]=[f for f,sha in manifest.items() if f not in ['composer.json','composer.lock'] and (not (wd/f).is_file() or hashlib.sha256((wd/f).read_bytes()).hexdigest()!=sha)]
provenance={'checked_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'commit':subprocess.check_output(['git','rev-parse','HEAD']).decode().strip(),'tree':subprocess.check_output(['git','rev-parse','HEAD^{tree}']).decode().strip(),'tracked_source_differences':changed,'matrix_differences_excluding_dependency_resolution':mat,'workflow_blob':subprocess.check_output(['git','hash-object','.github/workflows/ci.yml']).decode().strip(),'status':subprocess.check_output(['git','status','--porcelain=v1']).decode(),'snapshot_status_note':'Snapshot captured after the new evidence directory was created: only that untracked directory appears; no tracked source changes were present.'}
(p/'final-provenance.json').write_text(json.dumps(provenance,indent=2)+'\n')
assert not changed and not any(mat.values())
records=sorted([json.loads(f.read_text()) for f in (p/'results').glob('*.json')],key=lambda d:d['started_utc'])
assert len([r for r in records if r['exit_code']==255])==20
assert len([r for r in records if r['exit_code']==127])==4
assert all(r['exit_code']==0 or ('flex-' in r['label'] and r['exit_code']==255) or ('initial-gpg' in r['label'] and r['exit_code']==127) for r in records)
for r in records:
 if 'flex-' in r['label']:assert 'Compiled module inspection failed:' in (p/r['log']).read_text()
summary={'command_records':len(records),'exit_code_counts':dict(collections.Counter(r['exit_code'] for r in records)),'unexpected_failures':[],'recommendation':'NO-GO','blocker':'B1: all20Flexsmoketestsassertretiredinspectionformat','matrix':{}}
for job in mat:
 import re
 log=(p/'logs'/f'{job}-phpunit.log').read_text(); tests,assertions=map(int,re.search(r'OK \((\d+) tests, (\d+) assertions\)',log).groups())
 consumer=(p/'logs'/f'{job}-consumer.log').read_text(); assert consumer.count('PASS PHP ')==12
 summary['matrix'][job]={'tests':tests,'assertions':assertions,'consumer_installs':12,'consumer_kernel_executions':24,'flex_failed':5,'other_recorded_checks_pass':True}
(p/'summary.json').write_text(json.dumps(summary,indent=2)+'\n')
lines=['# Executed command ledger','','All entries link to original logs and record actual exit codes. Command arrays are authoritative in `results/*.json`; this table uses shell quoting for readability. Environment overrides for matrix jobs are part of the `docker run`/`docker exec` commands. Exit255 is the uncorrected Flex blocker; initial exit127 is the missing QA-image GnuPG prerequisite, subsequently provisioned. Expected failing CLI cases are separately recorded with expected/actual exits in `agent-cli/results.json`.','','| Label | Exit | Seconds | Command |','|---|---:|---:|---|']
for r in records:
 cmd=shlex.join(r['command']).replace('|','\\|').replace('`','\\`')
 lines.append(f"| [{r['label']}]({r['log']}) | {r['exit_code']} | {r['elapsed_seconds']} | `{cmd}` |")
(p/'COMMANDS.md').write_text('\n'.join(lines)+'\n')
shutil.copy2('/tmp/sillbeam-final-phases.py',p/'helpers/phases.py')
print(json.dumps(summary,indent=2))
