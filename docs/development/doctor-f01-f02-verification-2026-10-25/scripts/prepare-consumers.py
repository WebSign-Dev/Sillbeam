"""Create new consumers from preserved acceptance fixtures; never edit the originals."""
from pathlib import Path
import shutil,json,subprocess,os,sys
root=Path(sys.argv[1]).resolve(); source=Path(sys.argv[2]).resolve(); old=Path(sys.argv[3]).resolve()
root.mkdir(); (root/'logs').mkdir(); package=root/'package';package.mkdir()
shutil.copytree(source/'src',package/'src')
for name in ['composer.json','LICENSE','README.md']:shutil.copy2(source/name,package/name)
for line in ['74','81']:
 app=root/('sf'+line);app.mkdir(); original=old/('sf'+line+'-BC-r2')
 for name in ['bin/console','src/Kernel.php','config/bundles.php','config/routes.yaml','config/routes/framework.yaml','config/services.yaml','config/packages/framework.yaml','.env']:
  src=original/name
  if src.exists():
   dst=app/name;dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(src,dst)
 config=json.loads((original/'composer.json').read_text())
 config['repositories'][0]['url']=str(package)
 config['repositories'][0]['options']['reference']='none'
 config['config']['allow-plugins']={'symfony/flex':False,'symfony/runtime':True}
 (app/'composer.json').write_text(json.dumps(config,indent=4)+'\n');shutil.copy2(original/'composer.lock',app/'composer.lock')
 for label,args in [('install',['update','websign-dev/sillbeam-core','--no-plugins','--no-scripts','--no-interaction','--no-progress','--prefer-dist']),('autoload',['dump-autoload','--no-scripts','--no-interaction']),('platform',['check-platform-reqs'])]:
  with (root/'logs'/('fresh-'+line+'-'+label+'.log')).open('w') as log:
   result=subprocess.run(['composer',*args],cwd=app,stdout=log,stderr=subprocess.STDOUT)
  print(line,label,result.returncode,flush=True)
  if result.returncode:raise SystemExit(result.returncode)
