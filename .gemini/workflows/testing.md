**Title:** Analyze Documentation, Execute Tests, and Fix Bugs

**Context:** We have failing tests in the current test suite. Before modifying any code, it is
critical to understand the architecture and business rules established in the documentation. The
`docs/` directory serves as the single source of truth for the expected system behavior.

**Execution Instructions:** Please complete this task by strictly following these sequential steps:

1. **Study the Documentation:**

- Read and analyze all Markdown (`.md`) files and technical documentation within the `docs/`
  directory.
- Understand the system architecture, logic flow, and expected application behavior based on these
  documents.

2. **Run the Test Suite:**

- Execute the entire test suite in this repository.
- Identify all test cases that are failing or throwing errors.

3. **Analyze and Fix:**

- Compare the test failure outputs against the expected system behavior outlined in `docs/`.
- Perform bug fixes on the main source code so it aligns perfectly with the documentation.
- If you discover that a test case is outdated and contradicts the current documentation, update the
  test code accordingly to reflect the correct behavior.

4. **Final Verification:**

- Re-run the entire test suite.
- Ensure all tests pass successfully without introducing new errors or warnings.

**Acceptance Criteria:**

- The agent demonstrates an understanding of the `docs/` content through the implementation.
- All unit and integration tests execute successfully (100% pass rate).
- A Pull Request (PR) is generated, including a brief explanation of the root cause, the modified
  files, and the before/after test results.
