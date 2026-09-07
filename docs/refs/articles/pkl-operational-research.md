# PKL Operational Research — Real Problems & Manual Data-Storage Pain Points

> **Status:** Research input. Not a spec. Intentionally outside the spec system — findings describe
> the operational reality of Indonesian SMK PKL programs, not verifiable engineering requirements.
> **Marker:** Non-testable (`*` per spec-template convention).
> **Owner:** Documentation specialist (research context, not implementation).
> **Research window:** September 2026; sources span 2020–2026.

## Description

Consolidated evidence on how Indonesian SMK schools actually run their PKL (Praktik Kerja Lapangan)
programs and where manual data management causes operational harm. Synthesized from three web research
passes covering five points of view — school leadership, admin/Pokja PKL, teacher/pembimbing, student,
and industry/DUDI — plus the governing regulatory stack. Every finding cites a source and a URL;
`[VERIFIED]` marks findings backed by multiple independent sources, `[SINGLE]` single-source findings,
`[CONFLICT]`/`[GAP]` flag disagreement or absent evidence.

## Why This Is Outside the Spec System

Same rationale as [curriculum-compliance.md](curriculum-compliance.md): field evidence describes a
moving target (school SOPs, industry behavior, regulations) that engineering cannot test against.
The value here is **prioritization** — Internara features should map to the strongest, most-repeated
pain points below — not requirement extraction.

## Current Regulatory Stack (binding context)

| Regulation | Effect |
|------------|--------|
| **Permendikbud No. 50/2020** | Governing PKL regulation, still in force ("Berlaku"); defines cooperation obligation, workplace types, dual supervision, OHS, certificate signing by workplace leader. `[VERIFIED]` |
| **Kepmendikbudristek No. 262/M/2022** (amending 56/M/2022) | PKL becomes a **mandatory mapel** in Kurikulum Merdeka for class XII, minimum **6 months / 792 hours**; a formal cooperation agreement ("naskah kerja sama") must precede PKL. `[VERIFIED]` |
| **Permendikbudristek No. 12/2024** (Kurikulum) | Reconciled PKL as "wahana pembelajaran di dunia kerja"; drove the 2024 revised PKL guide. `[VERIFIED]` |
| **Panduan PKL Edisi Revisi 2024** | Current operational guide from Direktorat SMK. `[VERIFIED]` |
| **Inpres No. 9/2016**, **UU No. 20/2023** | Revitalisasi SMK (stronger DUDI role); Sisdiknas art. 15 (vocational prep for work). `[VERIFIED]` |
| **Kemenperin Link & Match** (BPSDMI) | Parallel program mandating industry participation (curriculum alignment, magang, certification, Teaching Factory). `[VERIFIED]` |
| **Permendikdasmen No. 11/2025** | Teacher workload 24 JP floor; PKL school-supervision is **not** in the credited equivalency table. `[VERIFIED]` |
| **BAN-PDM** (R/2026) | BAN-S/M transitioned; physical evidence folders for accreditation include PKL/partnership docs. `[SINGLE]` |

> **Duration conflict:** one Kurikulum Merdeka guide source says 6–10 months; the regulatory baseline
> is 6 months / 792 hours. Treat 6 months as the floor. `[CONFLICT]`

## Scale (why this matters)

- **14,391 SMK**, **4.88M students** (2024/25): Gr.10 1.71M, Gr.11 1.63M, Gr.12 1.53M; 132,570 productive
  teachers; 82.5% graduate absorption; link & match 74.2%. `[VERIFIED]`
- Gr.11+Gr.12 ≈ **3.16M PKL-candidate students/year** across ~14k schools (inference, `[GAP]` — no
  authoritative "students who complete PKL/year" headline exists).
- Dapodik national sync ~96.8% (447k schools); provincial rates ~84–99% — the reporting discipline the
  PKL program rides. `[VERIFIED]`

---

## POV 1 — School Leadership (Principal / Vice-Principal / Waka Kurikulum)

**Manual-is-root-cause.** PKL data management is overwhelmingly non-computerized — info shared "secara
lisan dan hanya mengandalkan kertas pengumuman" (SMK Darul Ulum 2023); "masih belum terkomputerisasi"
with info via lisan/papan pengumuman/chat causing "kerancuan dalam proses pengajuan dan penempatan
siswa" (SMKN 1 Pengasih 2024); "masih berjalan secara manual" (SMK Plus Fatahillah). `[VERIFIED]`

**WhatsApp is the de-facto platform and is painful at scale.** SMKN 2 Padang (TSAQOFAH Nov 2025): all
attendance/reports/documentation via grup WhatsApp → teacher-device storage limits + "penumpukan arsip
fisik" (physical archive pile-up) slowing inspection and delaying grading; the study names the **Pokja
role** and calls for an integrated management system. `[VERIFIED]`

**Monitoring collapses by distance.** Palangka Raya study (Kanderang Tingang, Jan 2026): city sites
(<15 km) 3–4 visits; out-of-town (50–150 km) 1 visit; remote (>150 km) 0–1 visits, written reports only
(average 1.6 visits/period). Low supervision correlates with soft-skill unpreparedness; recommendation
is digital tele-monitoring + "revitalisasi Pokja PKL." `[VERIFIED]`

**Placement is by domicile, not competency.** Students placed "tidak sesuai kompetensi"; institutional
interest dominates over student preference (UB repository 2023; UPI thesis; ResearchGate 2023; Darul
Ulum). `[VERIFIED]`

**Coordination pain.** "Komunikasi tidak efisien, pelaporan yang terlambat, kesulitan memantau
perkembangan siswa" (UNS SHES 2024). `[SINGLE but corroborated]`

**MoU/PKS paperwork is heavy and per-partner.** Each DUDI requires its own signed per-period agreement
(SMKN 1 Tulus Rejo 3-year MoU template 2024 ⸺ hundreds of individual paper agreements per school with
many partners). `[SINGLE artifact]`

**Grading is a manual multi-input aggregate.** Value assembled from jurnal + final presentation +
DUDI certificate across separate 0–100 sheets (site leader, field advisor, seminar — Scribd format),
"kurang efisien dalam mengolah nilai… kesalahan… lambatnya penyerahan data" (JRAMI Unindra), delayed by
physical pile-up (SMKN 2 Padang). Then re-entered into e-Rapor and pushed to Dapodik (a second/third
system). `[VERIFIED]`

## POV 2 — Admin / Pokja PKL (Coordinator / Working Committee)

**The coordinator is the manual hub.** All info, placement, journals, reports flow through the
coordinator's paper/chat stack (`[VERIFIED]` — coordinator-as-informant in SMKN 1 Pengasih, Darul Ulum).

**DIY stopgaps fill the gap — with data-ownership risk.** SMART PKL SMKN 2 Wajo is an AppSheet app
storing data on a **shared personal Gmail cloud account** (data ownership/versioning risk); Google
Sites hubs (PKL STEMBA, SMK Cordova) and school e-jurnal portals (SMKN 1 Gombong) proliferate. `[VERIFIED]`

**Monitoring-visit scheduling degrades with distance; logbook collection is painful.** 0–1 visits for
remote sites (Kanderang Tingang); chat-storage + physical packet collection slows everything (SMKN 2
Padang). `[VERIFIED]`

**Industry feedback is weak/asymmetric.** MoUs "belum sepenuhnya terealisasi, bentuk kegiatan kemitraan
tidak jelas, kurangnya kepedulian kedua pihak" (ResearchGate 2023); weak industry feedback recurring
managerial problem (Kanderang Tingang). `[VERIFIED]`

**Re-typing across systems.** Admin must manually re-enter partnerships into Dapodik (PKS records) +
PKL rombel/teacher data while assembling physical BAN-PDM evidence folders. `[VERIFIED/PARTIAL]`

## POV 3 — Guru / Pembimbing (teacher-supervisor)

**Supervision burden is real and uncredited.** Monitoring only 1×/month over a 6-month PKL; teachers
can't know if students actually attend (SMKN 1 Gesi, UNS SHES 2024). Planned 6× vs actual 2–3×
(Tamtama 1 Sidareja CIPP 2024). Constraint named as "lokasi PKL yang tersebar dan jauh, sistem
pelaporan manual, serta keterbatasan waktu guru pembimbing" (SMK Darussalam Balapulang, JATI 2026).
School-supervision is **not creditable** against the 24-JP workload floor (Permendikdasmen 11/2025). `[VERIFIED]`

**Attendance/logbook are fakeable.** "Sering terjadi ketidakjujuran siswa dalam pengisian absensi dan
logbook kegiatan harian" (SMKN 1 Sintuk Toboh Gadang 2022); paper books "mudah dimanipulasi" without
attached documentation (SMKN 1 Gesi). `[VERIFIED]`

**The fix-market is consistent.** Many independent build-journals converge on the same feature set:
QR-code/GPS/selfie check-in, daily e-logbook, real-time supervisor visibility, WhatsApp/Telegram
notifications (Sintuk Toboh Gadang, Gesi, Al Hidayah Cirebon, YPT Pringsewu, PKLTRACK, PKL Smart
commercial app). This is **strong market evidence for Internara's core attendance + logbook features**. `[VERIFIED]`

**Teacher observes many overlapping dimensions per visit** (attendance, work type, attitude, skill
progress, jurnal, plus industry-supervisor interview and documentation check — official Panduan
Monitoring). `[SINGLE]`

## POV 4 — Siswa / Peserta Didik (16–18 y/o intern)

**Placed by institution, not preference.** "Penempatan PKL… masih didominasi oleh kepentingan
institusi, tanpa mempertimbangkan preferensi siswa" (UPI 2025) — students want proximity + accessibility. `[VERIFIED]`

**Placement/jurusan fit is broken.** "Ketidaksesuaian struktural antara profil lulusan… dengan unit
kerja" (Jurnal P4I); a whole profile-matching research line exists because fit is routinely wrong. `[VERIFIED]`

**Unclear job descriptions; inadequate guidance.** "Kesulitan mencari tempat PKL sesuai jurusan,
kurangnya pemahaman siswa terhadap deskripsi pekerjaan… bimbingan dan pengawasan yang kurang memadai
dari guru pembimbing" (Tamtama 1 Sidareja). `[VERIFIED]`

**The daily logbook is the core — and it's paper, self-filled, easily falsified.** `[VERIFIED]`

**Grade outcome is opaque until the end.** Final value computed only after PKL ends
(80% rerata + 20% laporan/presentasi — SMK Tarakan cert format); criteria vary school-to-school and
carry "unsur subjektif" from industry supervisors (NeLiti). Students who skip admin paperwork hurt
their own grade (STekom). `[VERIFIED]`

**Digital readiness limits any online-first solution.** BPS 2024: 72.78% internet access, 68.65%
mobile ownership nationally; urban–rural household gaps of 5–20+ points (Jabar 92.8% vs 82.2%; Kepri
98.7% vs 87.7%). Rural students borrow devices/internet from another household for online tasks →
**offline/low-end-device support is a hard requirement**. `[VERIFIED]`

## POV 5 — Industri / DUDI

**Supervisors disengaged/time-limited.** "Minimnya keterlibatan beberapa mitra industri dalam
memberikan bimbingan langsung" (JIM Ulit Al-Bab Martapura Dec 2024); schools compensate with their own
visits. `[VERIFIED]`

**Distance degrades industry supervision too.** Same Kanderang Tingang study — "degradasi kualitas
bimbingan seiring bertambahnya jarak"; remote sites rely on WhatsApp/written reports only. `[VERIFIED]`

**Interns lack initiative; attendance without notice.** Students idle after a work order, weak
communication confidence (Kipu, DIJMS 2025, 3 companies); one company enforces "3-day no-show = sent
back to school." `[VERIFIED]` Repeated lateness/phone-use deters future MoU (SMKN 10 Bekasi 2024).

**MoU reluctance.** "Tidak semua DU/DI mau melakukan MoU" — some won't be bound by school rules; others
demand proof of student quality first; some set grade-score requirements. `[VERIFIED]` MoU portfolio
skews low-value: 65% office/service in-city; extractive under-represented due to **K3 + remote
locations** (Kanderang Tingang). `[SINGLE]`

**Different evaluation standards.** Schools weight administrative completeness (logbook); industry
weights soft skills (adversity quotient, time discipline). `[VERIFIED]`

**National partnership quality is only "cukup".** DUDI participation rated "cukup" not "baik"
(n=76, Gorontalo 2022); national partnership index **2.35/4** (Mitras DUDI, Dec 2024); many regular
SMKs run link-and-match "formalitas" without professional industry guidance (UNY/IMP review 2024). `[VERIFIED]`

**Certification is company-borne.** Art. 15 Permendikbud 50/2020: PKL completion certificate signed
by the workplace leader. `[VERIFIED]`

---

## Cross-Cutting Synthesis

1. **Manual, chat-and-paper, offline-first reality.** Everything flows through WhatsApp photos +
   paper books; final grades are assembled late from scattered, subjective components; placement is
   institutional convenience. The system Internara replaces.
2. **Single source of truth is absent** — the same data is re-typed across WhatsApp → Excel → e-Rapor →
   Dapodik (PKS records, rombel, teacher data), a direct, documentable re-entry burden. `[VERIFIED]`
3. **Distance is the universal friction** — supervision from school and industry both degrade with km.
   Tele-monitoring is the recommended fix (Pokja-led evaluation literature). `[VERIFIED]`
4. **Anti-fraud attendance is the industry-validated core feature** — QR + GPS + selfie convergence
   across independent papers + commercial tools is the strongest single product signal in the whole
   evidence base. `[VERIFIED]`
5. **Offline / low-end-device support is non-negotiable** given BPS urban–rural gaps. `[VERIFIED]`

## Evidence Gaps (`[GAP]` — thin or missing)

- **Lost-forms / version-conflict quantification** (inferred from stack, not measured).
- **National annual PKL-completion figure** (only school totals exist; 3.16M is an estimate).
- **Reported Dinas Pendidikan grade/nilai format** (only Dapodik/e-Rapor chain documented).
- **Quantified grade-delay time** (sources say "slow/late," none give hours/days).
- **Teacher workload numbers specific to PKL supervision** (students-per-supervisor, hours).
- **DUDI churn / attrition rates, industry-side cost burden, credential-verification volume.**
- **True industry-primary large-n surveys** (most studies are school-centric; Kipu 2025 + Gorontalo 2022 are exceptions).
- **Offline logbook submission tested at rural sites** (inferred from connectivity data).
- **Commercial ARD-class product pain** (only DIY AppSheet/Google/WhatsApp stacks surfaced).

## Quick References

- [curriculum-compliance.md](curriculum-compliance.md) — regulation mapping (companion article)
- [`../index.md`](../index.md) — refs index
- [`../../index.md`](../../index.md) — full documentation catalog
- Relevant specs feeding feature priorities: [`7C5WM-internship-lifecycle`](../../specs/7C5WM-internship-lifecycle.md), [`1KSWL-daily-activity`](../../specs/1KSWL-daily-activity.md), [`2EHSE-supervision`](../../specs/2EHSE-supervision.md), [`J9GBH-placement`](../../specs/J9GBH-placement.md), [`ARDA6-assessment`](../../specs/ARDA6-assessment.md)