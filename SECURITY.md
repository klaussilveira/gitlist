# Security Policy

## Supported versions

Security fixes are made on `master` and shipped in the next release and in the nightly build.

| Version        | Supported |
| -------------- | --------- |
| 3.x (master)   | Yes       |
| 2.x            | Yes       |
| 1.x and older  | No        |

If you run 2.x or older, upgrade to the latest 3.x release.

## Reporting a vulnerability

Do not open a public issue, pull request or discussion for a security problem.

Report it privately through GitHub:
https://github.com/klaussilveira/gitlist/security/advisories/new

Include as much of the following as you can:

- The affected version or commit
- The configuration involved, and whether it differs from the defaults
- Steps to reproduce, or a proof of concept
- The impact you observed, and what you inferred but did not observe

## What to expect

- Acknowledgement of your report, usually within 7 days
- First assessment, and a request for more details if needed
- Fix developed in a private advisory, with credit to you unless you ask otherwise
- Disclosure date agreed with you, advisory is published once a fixed release is available

## Scope

GitList is a read-only repository viewer. It has no accounts and no authentication of its own, and it expects access control to come from the web server or the network.
In scope:

- Code execution, file writes or file reads outside the configured repositories
- Argument or command injection into `git` or `hg`
- Cross-site scripting or other injection through repository content (file contents, commit messages, ref names, author data)
- Disclosure of repositories that are excluded by configuration

Out of scope:

- Problems that require write access to the GitList installation directory
- Missing hardening headers or rate limiting with no demonstrated impact
- Installations that go against the documented setup, such as repositories writable by the web server user
