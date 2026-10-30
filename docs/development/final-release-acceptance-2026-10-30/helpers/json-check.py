import json,pathlib,collections
from jsonschema import Draft202012Validator
root=pathlib.Path.cwd(); data=json.loads((root/'build/docs/api/sillbeam-core.en.json').read_text()); schema=json.loads((root/'docs/reference/phpdoc-api.schema.json').read_text());Draft202012Validator.check_schema(schema);Draft202012Validator(schema).validate(data)
ids=[x['id'] for x in data['elements']];assert len(ids)==len(set(ids));assert ids==sorted(ids);assert data['language']=='en' and data['version']=='0.3.0'
for forbidden in ['\\GraphView','\\SvgGraph','\\ModuleInspectionPresentation','\\InstallationInspector']:
 assert not any(forbidden in i for i in ids),forbidden
assert any(i.endswith('SillbeamKernel') for i in ids)
assert any('::' in i and not '::$' in i for i in ids)
result={'draft':'2020-12','schema_valid':True,'document_valid':True,'unique_sorted_ids':True,'elements':len(ids),'kinds':dict(collections.Counter(e['kind'] for e in data['elements'])),'internal_implementation_probes_excluded':True}
(root/'docs/development/final-release-acceptance-2026-10-30/phpdoc-validation.json').write_text(json.dumps(result,indent=2)+'\n');print(json.dumps(result))
