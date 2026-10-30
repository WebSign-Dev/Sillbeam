import subprocess, pathlib, datetime, time, json, os, hashlib, sys
ROOT=pathlib.Path('/home/oeffe/PhpstormProjects/websign/sillbeam-core')
EVIDENCE=ROOT/'docs/development/b1-follow-up-2026-10-30'

def run(label, command, cwd=None, env=None, timeout=1800):
    logfile=EVIDENCE/'logs'/f'{label}.log'; resultfile=EVIDENCE/'results'/f'{label}.json'
    logfile.parent.mkdir(parents=True,exist_ok=True);resultfile.parent.mkdir(parents=True,exist_ok=True)
    started=datetime.datetime.now(datetime.timezone.utc).isoformat();t=time.monotonic()
    actual=os.environ.copy(); actual.update(env or {})
    record={'label':label,'command':command,'cwd':str(cwd or ROOT),'environment_overrides':env or {},'started_utc':started,'log':str(logfile.relative_to(EVIDENCE))}
    with logfile.open('wb') as log:
        try:
            p=subprocess.run(command,cwd=str(cwd or ROOT),env=actual,stdout=log,stderr=subprocess.STDOUT,timeout=timeout)
            record['exit_code']=p.returncode
        except subprocess.TimeoutExpired:
            record['exit_code']=124;record['error']='Timed out';log.write(b'\nACCEPTANCE RUNNER: TIMEOUT\n')
        except Exception as e:
            record['exit_code']=125;record['error']=repr(e);log.write(('\nACCEPTANCE RUNNER: '+repr(e)+'\n').encode())
    record['elapsed_seconds']=round(time.monotonic()-t,3);record['finished_utc']=datetime.datetime.now(datetime.timezone.utc).isoformat()
    resultfile.write_text(json.dumps(record,indent=2,ensure_ascii=False)+'\n')
    print(f'{label}: exit {record["exit_code"]}, {record["elapsed_seconds"]}s',flush=True)
    return record

if __name__=='__main__':
    run(sys.argv[1],sys.argv[2:])
