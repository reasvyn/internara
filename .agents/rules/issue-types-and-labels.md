# Issue Types & Labels — Classification and Triage

Every issue is classified by type before it is written. The type drives the template sections it
will include (bugs get Reproduction; features get Acceptance Criteria), the label it carries, and
its severity/priority framing. Misclassification is not cosmetic — it misroutes the issue into the
wrong workflow and the wrong dashboard.

---

## Choose the Type First

**What it enforces:** Before writing body text, select the issue type from the eight defined types
and apply the matching label. The type is the anchor for everything else in the issue.

**Why it matters:** The type determines which template sections are mandatory (reproduction for bugs,
acceptance criteria for features) and which label the tracker receives. An untyped or mis-typed issue
forces every reader to infer intent, which is exactly the ambiguity issues exist to remove.

**How to apply:** Match the concern to the type table:

| Type            | Label           | When to Use                                   |
| --------------- | --------------- | --------------------------------------------- |
| **Bug**         | `bug`           | Behavior doesn't match specification          |
| **Feature**     | `enhancement`   | New capability (`feature` is alias, same color) |
| **Security**    | `security`      | Security vulnerability                        |
| **Refactor**    | `refactor`      | Structure improvement without behavior change |
| **Performance** | `performance`   | Speed/memory optimization (`perf` is alias)   |
| **Test**        | `test`          | Test addition or fixes                        |
| **Docs**        | `docs`          | Documentation update (`documentation` alias)  |
| **Chore**       | `chore`         | Tooling, dependencies, config — create label if missing |

> **Alias note:** `feature` ↔ `enhancement` (`#a2eeef`), `documentation` ↔ `docs`, `perf` ↔ `performance`
> are interchangeable. Prefer the canonical name in the table above.

**Pitfalls to avoid:**

- Calling a spec-mismatch a "feature request" — a behavior that contradicts the spec is a `bug`
  referencing the violated requirement ID.
- Using `chore` for real behavior changes — `chore` is tooling/config, not feature work in disguise.
- Labeling every non-bug as `enhancement` — refactors, perfs, and tests are distinct types with
  distinct workflows.

**Verification:** The concern maps to exactly one row in the type table; the label applied equals
that row's label.

---

## Label Wajib — Mandatory Labels (Aturan Pelabelan Wajib)

**What it enforces:** Setiap issue WAJIB membawa **minimal 3 label wajib** tanpa kecuali.
Issue yang kekurangan label wajib dianggap **belum siap triage** dan harus dilengkapi sebelum
di-assign.

**Why it matters:** Label wajib adalah dimensi filter yang dipakai dashboard, `scan_issues.py`,
dan proses triage. Tanpa ketiganya, issue hilang dari laporan (mis. bug `critical` tanpa label
`P0` tidak muncul di antrian urgent), prioritas tidak dapat diurutkan, dan severity tidak dapat
diagregasi. Satu label wajib yang hilang = satu sumbu triage yang buta.

**How to apply:** Saat membuat issue, terapkan **satu label dari masing-masing kelompok wajib**:

| Kelompok Wajib | Pilihan (pilih TEPAT SATU) | GitHub Label | Warna | Makna |
| -------------- | --------------------------- | ------------ | ----- | ----- |
| **1. Type** | Bug / Feature / Security / Refactor / Performance / Test / Docs / Chore | `bug`, `enhancement`/`feature`, `security`, `refactor`, `performance`, `test`, `docs` | lihat tabel Registry | Jenis pekerjaan — menentukan workflow & template |
| **2. Severity** | Critical / High / Medium / Low | `critical`, `high`, `medium`, `low` | `B60205` / `d93f0b` / `fbca04` / `0e8a16` | Dampak kerusakan terhadap sistem/pengguna |
| **3. Priority** | P0 / P1 / P2 / P3 | `P0`, `P1`, `P2`, `P3` | `B60205` / `D93F0B` / `FBCA04` / `0E8A16` | Urgensi eksekusi — kapan harus dikerjakan |

Ringkasan aturan:

```
issue.labels = 1×Type  +  1×Severity  +  1×Priority  [+ opsional: Area/Status/Auxiliary]
               ─────────  ────────────  ────────────
                wajib       wajib         wajib         opsional
```

- **Type:** tepat satu. Dua type = dua concern → split issue (One Issue = One Concern).
- **Severity:** tepat satu. Nilai berdasarkan dampak nyata di Scope & Impact, bukan firasat.
- **Priority:** tepat satu. Nilai berdasarkan urgensi bisnis/rilis, bukan copy dari severity.

Contoh pelabelan minimal yang valid:

- `bug` + `critical` + `P0` — kebocoran data yang harus dihentikan sekarang
- `enhancement` + `medium` + `P2` — fitur baru dengan dampak sedang, siklus ini
- `refactor` + `low` + `P3` — perapian struktur, backlog

**Pitfalls to avoid:**

- Hanya memberi label `bug` tanpa severity/priority — issue tidak terurut di board triage.
- Memberi dua severity (`critical` + `high`) untuk "menekankan" — severity adalah skala tunggal,
  pilih yang paling akurat.
- Menyalin severity ke priority (`critical` → `P0` selalu) — severity dan priority adalah dua sumbu
  terpisah (lihat § Severity vs. Priority).
- Menganggap priority opsional karena sudah ada severity di body — body tidak terfilter, label yang
  terfilter.

**Verification:**

- [ ] Issue memiliki **tepat 1** label Type dari daftar tipe
- [ ] Issue memiliki **tepat 1** label Severity (`critical`/`high`/`medium`/`low`)
- [ ] Issue memiliki **tepat 1** label Priority (`P0`/`P1`/`P2`/`P3`)
- [ ] Ketiga label ada di registry repo (`gh label list`); tidak ada label ad-hoc
- [ ] Nilai severity & priority masing-masing dibenarkan di kolom Scope & Impact

---

## Label Dasar — Base Label Taxonomy (Kategori Label Dasar)

**What it enforces:** Label dasar adalah **kumpulan label fondasi** yang membentuk kosakata tetap
repo. Label dasar terbagi dalam kategori dengan peran berbeda; label wajib adalah subset dari label
dasar (Type + Severity + Priority), sedangkan kategori lain bersifat kontekstual.

**Why it matters:** Tanpa taksonomi, label tumbuh liar — `critical`, `Critical`, `crit`, `urgent`
menjadi empat label untuk satu makna dan menghancurkan filter. Taksonomi memastikan satu makna =
satu label, dan setiap label punya kategori yang menjelaskan kapan ia dipakai. AI agent dan
maintainer baru cukup membaca tabel ini untuk melabeli dengan benar tanpa menebak.

**How to apply:** Gunakan kategori berikut sebagai peta. Pilih label hanya dari kategori yang
relevan dengan issue; jangan mencampur kategori secara berlebihan.

### Kategori Label Dasar

| Kategori | Peran | Label | Wajib? | Kapan Dipakai |
| -------- | ----- | ----- | ------ | ------------- |
| **Type** | Jenis pekerjaan | `bug`, `enhancement`, `feature`, `security`, `refactor`, `performance`, `test`, `docs`, `documentation`, `technical-debt`, `dependencies` | **Ya — 1 label** | Setiap issue, sesuai § Choose the Type First |
| **Severity** | Tingkat kerusakan | `critical`, `high`, `medium`, `low` | **Ya — 1 label** | Setiap issue, berdasarkan dampak (lihat § Severity vs Priority) |
| **Priority** | Tingkat urgensi | `P0`, `P1`, `P2`, `P3` | **Ya — 1 label** | Setiap issue, berdasarkan urgensi rilis |
| **Status** | Keadaan issue | `duplicate`, `wontfix`, `invalid`, `question` | Tidak — hanya saat kondisi terpenuhi | Oleh triage: duplikat, tidak akan dikerjakan, tidak valid, butuh info |
| **Auxiliary** | Sinyal kontribusi | `good first issue`, `help wanted` | Tidak — selektif | Tugas kecil terdokumentasi baik / butuh bantuan eksternal |
| **Area / Module** | Lokasi kode | `core`, `backend`, `database`, `auth`, `settings`, `reports`, `production`, `php`, `javascript` | Tidak — 0..2 label | Saat issue terikat modul/bahasa spesifik; jangan spam semua modul |
| **Audit** | Temuan audit | `qa-audit`, `quality`, `performance`, `accessibility` | Tidak — oleh audit | Diterapkan oleh `qa-protocol`, `arch-guard`, `scan_*.py` |
| **Series** | Fase roadmap | `Series: ARC01-INIT` … `Series: ARC01-GAP` (10 labels) | Tidak — oleh planning | Diterapkan saat issue dipetakan ke fase `docs/specs/index.md` |

### Aturan Kombinasi Label Dasar

1. **Minimal 3, maksimal ~6.** Wajib 3 (Type+Severity+Priority). Tambahan Area/Auxiliary/Audit
   hanya jika benar-benar relevan. Lebih dari 6 label = sinyal issue terlalu luas (split).
2. **Satu concern = satu Type.** Jangan kombinasikan `bug` + `enhancement` dalam satu issue.
3. **Severity dan Priority tidak saling menggantikan.** `critical` + `P3` adalah kombinasi valid
   (kerusakan besar tapi jarang terjadi / belum dipakai) — jangan dipaksa sinkron.
4. **Area label secukupnya.** Maksimal 2 area label per issue; jika menyentuh >2 modul, issue
   kemungkinan terlalu besar untuk satu sesi (L-size → split).
5. **Series & Audit dikelola tooling.** Jangan menambahkan `Series:` atau `qa-audit` manual
   kecuali issue memang hasil audit/planning yang memetakannya.

**Pitfalls to avoid:**

- Menambahkan label Area untuk setiap modul yang "mungkin tersentuh" — hanya modul yang file-nya
  tercantum di Scope & Impact.
- Memberi `good first issue` pada issue yang butuh pemahaman domain mendalam — label ini adalah
  janji kepada newcomer.
- Membuat label baru (`urgent`, `blocker`, `nice-to-have`) alih-alih memakai `P0`–`P3` — gunakan
  kosakata yang ada.

**Verification:**

- [ ] Setiap label pada issue ada di Registry di bawah (atau diajukan resmi sebelum dipakai)
- [ ] Kombinasi label mengikuti aturan minimal 3 / maksimal ~6 / satu Type
- [ ] Tidak ada duplikasi makna (mis. `enhancement` + `feature` bersamaan tidak perlu — pilih satu)

---

## Severity vs. Priority — Two Axes, Both Filled

**What it enforces:** Severity (the damage an issue causes) and priority (the urgency of fixing it)
are distinct and both are explicitly set **as GitHub labels**. Severity is an attribute of the defect;
priority is a judgment call about sequencing.

**Why it matters:** A low-severity issue can be high-priority (a cosmetic bug in the login screen
during a marketing push) and a high-severity issue can be low-priority (a rare data-loss path in a
feature nobody uses yet). Collapsing them into one number loses the information triage runs on.

**How to apply:**

### Severity — label `critical` / `high` / `medium` / `low`

| Label | Color | Definisi | Contoh |
| ----- | ----- | -------- | ------ |
| `critical` | `#B60205` | Kehilangan data / pelanggaran keamanan / sistem tidak dapat dipakai | Data placement hilang, auth bypass, migrasi merusak FK |
| `high` | `#d93f0b` | Alur inti terblokir untuk banyak pengguna, tanpa workaround mudah | Registrasi gagal untuk semua siswa, login 500 |
| `medium` | `#fbca04` | Alur rusak tapi ada workaround atau dampak terbatas | Filter laporan salah tapi ekspor CSV masih benar |
| `low` | `#0e8a16` | Kosmetik / minor / edge case jarang | Typo di label form, alignment tabel |

Base severity on the measurable impact in the Scope & Impact section.

### Priority — label `P0` / `P1` / `P2` / `P3`

| Label | Color | Definisi | SLA Triage |
| ----- | ----- | -------- | ---------- |
| `P0` | `#B60205` | Kritis / Immediate — hentikan pekerjaan lain, perbaiki sekarang | Dikerjakan < 24 jam, hotfix jika perlu |
| `P1` | `#D93F0B` | High — batch berikutnya | Sprint / sesi berikutnya |
| `P2` | `#FBCA04` | Medium — siklus ini | Backlog terurut, 1–2 sprint |
| `P3` | `#0E8A16` | Low — backlog | Kapan ada kapasitas / good first issue |

Base priority on release timing and population. Tulis alasan di Impact description.

### Matriks Severity × Priority (kombinasi yang sering salah dipahami)

|  | `P0` | `P1` | `P2` | `P3` |
|--|------|------|------|------|
| `critical` | Data loss yang sedang terjadi → hotfix | Data loss di fitur yang baru rilis minggu depan | Data loss di fitur yang belum dipakai | Critical tapi sudah ada mitigasi manual |
| `high` | Registrasi minggu ini terblokir | Alur inti rusak, rilis 2 minggu lagi | Alur inti rusak di modul jarang dipakai | — |
| `medium` | — | Workaround ada tapi menyakitkan saat demo | Kasus umum, ada workaround | Minor flow, tidak mendesak |
| `low` | Typo di halaman login saat marketing push | — | Typo umum | Typo di halaman admin internal |

> Kunci: **Severity menjawab "seberapa parah jika dibiarkan?"**, **Priority menjawab "kapan harus
> dikerjakan?"**. Keduanya diisi terpisah di Scope & Impact dan sebagai label.

**Pitfalls to avoid:**

- Making priority a copy of severity (`critical` selalu `P0`).
- Filling severity but leaving priority to "default" — the triage queue needs both labels.
- Mengisi Priority di body sebagai `urgent/high/medium/low` (istilah lama) — gunakan `P0`–`P3`.

**Verification:** Both labels are present (`critical`/`high`/`medium`/`low` + `P0`/`P1`/`P2`/`P3`),
and each is justified by the impact description. Body fields `Severity` and `Priority` in the
Scope & Impact table match the labels exactly.

---

## Labels Come From the Repo's Defined Set — Registry

**What it enforces:** Only repository-defined labels are applied — the registry below is the fixed
set. No ad-hoc label names. Jika label yang dibutuhkan belum ada, ajukan pembuatan via
`gh label create` sebelum dipakai — jangan pakai nama ad-hoc di issue.

**Why it matters:** `scan_issues.py` and the tracker's filters group by fixed labels. An
out-of-repo label silently drops the issue from every summary report, so tracking becomes
incomplete without any visible error.

**How to apply:** Apply the mandatory 3 (Type + Severity + Priority) plus any contextual label
that already exists in the registry. The full registry (sinkron dengan `gh label list` per
2026-08-25):

### Type & Base

| Label | Color | Description |
| ----- | ----- | ----------- |
| `bug` | `#d73a4a` | Something isn't working |
| `enhancement` | `#a2eeef` | New feature or request |
| `feature` | `#a2eeef` | New feature or request (alias `enhancement`) |
| `security` | `#B60205` | Security and governance related |
| `refactor` | `#006b75` | Code refactoring |
| `technical-debt` | `#fbca04` | Technical debt or refactoring needs |
| `performance` | `#1D76DB` | Performance findings |
| `test` | `#0e8a16` | Testing |
| `docs` | `#5319e7` | Documentation |
| `documentation` | `#0075ca` | Improvements or additions to documentation |
| `dependencies` | `#0366d6` | Pull requests that update a dependency file |

### Severity (Wajib — pilih 1)

| Label | Color | Description |
| ----- | ----- | ----------- |
| `critical` | `#B60205` | Critical severity |
| `high` | `#d93f0b` | High severity |
| `medium` | `#fbca04` | Medium severity |
| `low` | `#0e8a16` | Low severity |

### Priority (Wajib — pilih 1)

| Label | Color | Description |
| ----- | ----- | ----------- |
| `P0` | `#B60205` | Priority: Critical / Immediate |
| `P1` | `#D93F0B` | Priority: High |
| `P2` | `#FBCA04` | Priority: Medium |
| `P3` | `#0E8A16` | Priority: Low |

### Status

| Label | Color | Description |
| ----- | ----- | ----------- |
| `duplicate` | `#cfd3d7` | This issue or pull request already exists |
| `wontfix` | `#ffffff` | This will not be worked on |
| `invalid` | `#e4e669` | This doesn't seem right |
| `question` | `#d876e3` | Further information is requested |

### Auxiliary

| Label | Color | Description |
| ----- | ----- | ----------- |
| `good first issue` | `#7057ff` | Good for newcomers |
| `help wanted` | `#008672` | Extra attention is needed |

### Area / Module

| Label | Color | Description |
| ----- | ----- | ----------- |
| `core` | `#0366d6` | Core module |
| `backend` | `#0052CC` | Backend logic and services |
| `database` | `#D93F0B` | Database schema and migration changes |
| `auth` | `#5319e7` | Auth module |
| `settings` | `#fbca04` | Settings module |
 | `reports` | `#0e8a16` | Reports module |
 | `production` | `#0e8a16` | Production deployment and VPS logs |
 | `php` | `#45229e` | Pull requests that update php code |
 | `javascript` | `#168700` | Pull requests that update javascript code |

### Audit

| Label | Color | Description |
| ----- | ----- | ----------- |
| `qa-audit` | `#0E8A16` | Findings from QA Protocol blind audit |
| `quality` | `#5319E7` | Code quality findings |
| `accessibility` | `#D4C5F9` | WCAG accessibility findings |

### Series (Roadmap — dikelola planning)

| Label | Color | Description |
| ----- | ----- | ----------- |
| `Series: ARC01-INIT` | `#FBCA04` | Genesis phase tasks |
| `Series: ARC01-CORE` | `#0E8A16` | Core Engine phase tasks |
| `Series: ARC01-USER` | `#1D76DB` | Identity phase tasks |
| `Series: ARC01-INST` | `#D93F0B` | Institutional phase tasks |
| `Series: ARC01-OPER` | `#006B75` | Operational Layer tasks |
| `Series: ARC01-FEAT` | `#5319E7` | Assessment & Workspaces tasks |
| `Series: ARC01-ORCH` | `#B60205` | Administrative Orchestration tasks |
| `Series: ARC01-INTEL` | `#0052CC` | Reporting & Intelligence tasks |
| `Series: ARC01-BOOT` | `#A2EEEF` | System Initialization tasks |
| `Series: ARC01-GAP` | `#E99695` | Integrative Excellence tasks |

**Pitfalls to avoid:**

- Combining `bug` with both `security` and `performance` — that is three concerns (one-issue-one-concern
  applies to labels too).
- Creating a new label at submission time instead of using the registry — ajukan `gh label create`
  terlebih dahulu, lalu pakai.
- Menggunakan label bahasa campuran (`kritikal`, `tinggi`) — registry memakai `critical`/`high`/`medium`/`low`
  dan `P0`–`P3`.

**Verification:** Every label on the issue exists in the registry above; the label-to-type mapping is
exact; `gh label list` contains the label.

---

## Auxiliary Labels Are Applied Deliberately

**What it enforces:** `good first issue`, `help wanted`, `duplicate`, and `wontfix` (plus `invalid`,
`question`) are applied only when their conditions hold — never out of convenience.

**Why it matters:** `good first issue` routes the issue to newcomers; misusing it floods that backlog
with work that isn't newcomer-sized. `duplicate` is an evidence statement about tracker health;
`wontfix` is a maintained decision that must survive the issue's lifetime (and links to the
reasoning).

**How to apply:**

- `good first issue`: scope is small, well-documented, no deep domain prerequisite. Selalu kombinasikan
  dengan `P3` atau `P2` (bukan `P0` — newcomer tidak mengerjakan hotfix).
- `help wanted`: the maintainers are inviting outside contribution.
- `duplicate`: applied by triage when this issue duplicates a tracked concern — the issue is then
  closed with a link to the original.
- `wontfix`: applied when the decision is made to not resolve; the body records why.
- `invalid` / `question`: applied by triage when the report is not actionable or needs clarification;
  issue stays open until clarified, then re-labeled or closed.

**Pitfalls to avoid:**

- Marking every trimmed-down bug as `good first issue`.
- Applying `wontfix` without a recorded reason — closed-without-reason issues get re-filed.

**Verification:** Each auxiliary label's condition is true at application time and recorded in the
issue body.

---

## References

| Topic                        | Asset                                       |
| ---------------------------- | ------------------------------------------- |
| Label mapping to quality     | `rules/issue-quality.md` (this skill)       |
| Unified template             | `rules/issue-template.md` (this skill)      |
| Issue scanner & labels       | `tools/scan_issues/cli.py`                    |
| Label registry (live)        | `gh label list --limit 100`                 |
