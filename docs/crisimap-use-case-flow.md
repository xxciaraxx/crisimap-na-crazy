# CRISIMAP use-case flow

The use-case source diagram is [crisimap-use-case-diagram.puml](./crisimap-use-case-diagram.puml).
It can be rendered with any PlantUML-compatible editor or extension.

## Corrected workflow

1. A Social Worker Officer or Focal Person authenticates and creates or updates an
   intake sheet.
2. Intake validation and applicant-history/duplicate checking are mandatory parts
   of editing an intake sheet.
3. The Social Worker Officer submits the intake for Focal Person eligibility
   review. A Focal Person who creates the intake can submit it to the MSWDO Head
   approval stage.
4. The Focal Person reviews eligibility. Rejection is a conditional extension of
   review; an eligible case creates an assistance application.
5. The MSWDO Head approves or rejects the assistance application. Approval
   includes checking the eligibility rules.
6. The Focal Person releases assistance only after the application is approved.
7. Report export is optional and therefore extends report generation. Household
   data and case documents are also optional extensions of case-record management.

`<<include>>` is used for behavior that is required by the base use case.
`<<extend>>` is used for optional or condition-dependent behavior. The original
flat diagram showed the available features and actor links, but it did not show
these workflow dependencies, so it was incomplete as a behavioral use-case
diagram.
