# Structured architecture findings

Keep all existing rules and strict container-build rejection. Add immutable findings (rule, severity, description, scalar context), one deterministic deduplicating collector, an analysis result and a LogicException-compatible aggregate exception. Severity error means a rule violation; fatal means the remaining analysis cannot be trusted. A reusable ArchitectureAnalyzer coordinates discovery, graph checks and code dependencies. Strict compile/analyze entry points continue to reject every invalid result.

1. Add failing tests for individual/combined findings, ordering/deduplication, independent graph/code violations, fatal source/ownership failures and strict kernel rejection.
2. Replace graph validation's early throws with collection and safe traversal of known edges. Preserve identity, ownership, dependency, cycle, activation and host rules. Do not publish invalid candidate graphs as runtime registries.
3. Collect code-reference violations while retaining exact source context and reference evidence. Explicitly classify ambiguous discovery/source-index failures; do not catch arbitrary programming errors as architectural findings.
4. Integrate the shared analyzer into the extension; document the reusable API, rule IDs, ordering and fatal behavior. Leave CLI/reporting formats unchanged.
5. Run unit/integration suite, PHPStan, Deptrac, coding standards and isolated consumers/Flex. Review for weakened boundaries or misleading partial results. Changes remain local; no push or release actions.

Review focus: unknown edges must not crash cycle/closure checks; cycle traversal must terminate; disabled modules remain excluded as activation roots; cross-project private access remains forbidden; ambiguous identities must not drive config/activation analysis; fatal errors never yield a usable graph; collector state must not leak between runs.
