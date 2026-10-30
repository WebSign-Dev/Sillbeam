from importlib.machinery import SourceFileLoader
r=SourceFileLoader('runner','/home/oeffe/PhpstormProjects/websign/sillbeam-core/docs/development/b1-follow-up-2026-10-30/helpers/runner.py').load_module()
for n in range(1,11):
 record=r.run('php85-sf81-doctor-probe-'+str(n),['docker','exec','sillbeam-b1-doctor-probe','php','tools/test-flex-recipe-diagnostic.php'])
 if record['exit_code']: break
r.run('doctor-probe-cleanup',['docker','rm','-f','sillbeam-b1-doctor-probe'])
