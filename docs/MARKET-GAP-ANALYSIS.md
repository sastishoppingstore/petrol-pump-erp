# Pakistani Market Gap Analysis

Researched against live Pakistani petrol-pump software (petro.pk, PumpPOS, Jantrah
Tech / petrolpumpmanager.com, DigitalManager, Umaish Solutions, POS Pakistan,
FAIS Associates PPMS) and FBR/OGRA regulatory sources.

**Rule applied: nothing is removed.** Everything in the original 48-section spec
stays. The items below are **additions** that either close a compliance gap or
match what Pakistani operators already expect to exist.

---

## A. LEGAL / REGULATORY — completely missing, and mandatory

### A1. FBR Digital Invoicing (SRO 1006(I)/2021)

This is not optional for tier-1 retailers. Our current invoice spec says
"NTN/GST/VAT information **if configured**" — that is not sufficient.

The SRO specifies, and the printed invoice must carry:

| Requirement | Detail |
|---|---|
| FBR fiscal invoice number | `XXXXXX-DDMMYYHHMMSS-0001` |
| QR code | **7mm × 7mm**, scannable |
| Verification statement | "Verify this invoice through FBR Tax Asaan Mobile App or SMS at 9966…" (legible font, exact wording) |
| FBR logo | On the invoice |
| PoS Service Fee | **Rs. 1/- per invoice as a separate line item** |
| Buyer name / CNIC / NTN | Required when the customer is tax-liable, **or** invoice value **exceeds Rs. 100,000** |
| Business block | Business name, complete address, **STRN**, **NTN**, Tax Office / Formation, unique **PoS registration number** |
| Transaction block | Item-wise description, unit price **exclusive of tax**, item-wise tax rate, item-wise quantity, item-wise tax amount |
| Totals block | Total sale value, tax charged, discount (separate line), PoS service fee, total payable, total received |
| Payment mode | Cash / credit / debit card / cheque / gift vouchers |

Also: integration must be performed by an **FBR-licensed integrator**
(Chapter XIV, Sales Tax Rules 2006). Our app must be *integrable* and produce the
mandatory fields, with the licensed integrator as the deployer.

**New modules:** `fbr_invoices`, `fbr_pos_config`, invoice template with QR, a
`FbrInvoiceService` that composes the fiscal number and payload, and a
`FbrIntegrationService` interface so a licensed integrator can be plugged in.

### A2. Provincial sales tax (PRA / SRB / KPRA / BRA)

Fuel is taxed **differently per province**. POS Pakistan and DigitalManager both
make per-province tax a core feature, not a setting.

- Punjab → **PRA**, Sindh → **SRB**, KPK → **KPRA**, Balochistan → **BRA**
- Each branch belongs to a province, which determines its tax authority
- Rates must be configurable **per province and per fuel/product class**
- Reports must split sales by province for consolidated tax filing

**New:** `provinces` on branches, `tax_authorities` seed, province-aware rate
resolution in the sale transaction, and a province column on every tax report.

### A3. Withholding tax (WHT) — Sections 236G / 236H / 236C

DIFBR and the Pakistani compliance market treat WHT as core. Not in our spec.

- Customer/supplier **filer status** determines the applicable WHT rate
- 236G applies on sales to a filer; 236H on purchases from a filer
- WHT is deducted at the point of invoice, not posted later by hand
- Needs a WHT ledger and a reconciliation register

**New:** `withholding_taxes` table, filer flag on customers/suppliers, automatic
deduction in the sale and purchase flows, WHT report.

---

## B. OIL MARKETING COMPANY (OMC) & FUEL SUPPLY — missing

Our spec treats the supplier as a generic vendor. Every Pakistani operator buys
from an **OMC**, and the accounting is different.

### B1. OMC khata (ledger)

- Suppliers are PSO, Shell, Total Parco, Attock Petroleum, PARCO, etc.
- Track **payment on account**, shipment receipts, and closing balance per OMC
- Payment by **cheque / cash / bank transfer** — cheque clearing status matters
- The headline question an owner asks daily: *"PSO ko kitna paisa dena hai?"*

**New:** `omcs` / supplier type, `shipment_receipts`, cheque status tracking
(`UNPRESENTED | IN_TRANSIT | CLEARED | BOUNCED`), and an OMC-payable dashboard.

### B2. Tanker drop verification

- Delivery arrives by **tanker**. The quantity in the tanker must be verified
  against the tank level before it is accepted.
- Records: tanker number, driver, delivery time, dip before, dip after,
  delivered quantity, density/temperature if available.

**New:** `fuel_deliveries` with tanker and driver, and a dip-before/dip-after
verification step.

### B3. Density & wet-stock

PumpPOS: "real-time **density** and dip reports, mapping dispenser volumetric
sales to electronic tank gauges."

- Fuel volume varies with **temperature**; density converts observed volume to
  standard litres, which is how OMC accounts are settled.
- ATG (Automatic Tank Gauge) probe support for real-time tank levels.

**New:** `tank_density_readings`, temperature correction factor, density-based
volume conversion, optional ATG integration point.

---

## C. MID-SHIFT PRICE CHANGE — a real correctness gap

OGRA revises fuel prices on a fortnightly cycle, and a revision can land **while a
shift is open**. Umaish describes the correct handling:

> "If a price changes during an open shift, the system **splits the litres at the
> changeover point** so revenue and margin stay accurate, and the price-change log
> doubles as an audit record."

Our current model values the whole sale at one rate. That is wrong across a price
boundary and would misstate margin on a high-volume day.

**New:** price-change events timestamped to the second, sale items able to span
two rate periods, and a price-change log that is also an audit record.

---

## D. NON-FUEL RETAIL — an entire missing revenue line

Roughly **20-40% of a typical Pakistani forecourt's gross profit** is not fuel.

- DigitalManager: "POS for **car wash**, **tyre shop**, **TUC shop**"
- Umaish: "Lubricants & Shop Inventory — non-fuel retail with its own margins
  and reorder", "including **barcode scanning**"
- petro.pk: "side revenue integration (**oil, filter, service**)"

Our spec has zero non-fuel capability. This is the single largest commercial gap.

**New modules:** `products`, `product_categories`, `product_purchases`,
`product_stock_movements`, `product_sales` (barcode POS), reorder level,
product-level margin reporting, and a combined "Forecourt P&L" that separates
fuel margin from shop margin.

---

## E. STAFF FINANCE & PAYROLL — incomplete

Our spec has employees and attendance, but not the money side.

- PumpPOS: "**Every staff advance is recorded digitally** with full payment
  history — no more verbal disputes." and "**One-Click Salary Posting** for all
  employees in one action. The system auto-calculates liabilities."
- Jantrah: "**Loan Management** — customer/partner loans"
- DigitalManager: "Time Attendance and **Payroll** system"

**New:** `employee_advances`, `staff_loans`, `payroll_runs`, `payroll_items`,
salary expense accrual into the P&L, and outstanding-advance reporting.

---

## F. CASH HANDLING DETAIL

### F1. Cash denomination breakdown

Standard practice at Pakistani stations for proving a cash shortage. Not in the
spec.

**New:** `cash_denominations` on shift close and on cash drops, so "I have
Rs. 4,300" can be verified against the counted notes.

### F2. Test sale / "test petrol"

Attendants consume fuel testing dispensers, or "test returns" flow through the
shift. Umaish lists "test returns" among the cash categories. Untracked, it shows
up as an unexplained meter gain.

**New:** a `TEST_RETURN` cash/movement type with a mandatory reason.

---

## G. COMMUNICATION — WhatsApp is the dominant channel

Every Pakistani competitor ships it first.

- petro.pk: "**1-click WhatsApp PDF & text ledger statements**"
- Jantrah: "**WhatsApp Alerts** for daily sales summaries, low stock warnings,
  shift closing reports, and critical transactions"
- Customer statements are shared on WhatsApp, not email

Email-only (as in our spec) is a real competitive disadvantage.

**New:** a `WhatsAppService` behind a pluggable gateway, plus notification
channels: `MAIL | WHATSAPP | SMS`.

---

## H. RELIABILITY & ACCESS

### H1. Offline operation and sync

PumpPOS and POS Pakistan both ship offline POS with later sync — forecourt
internet drops are routine in Pakistan and sales cannot stop.

**New:** a local queue table for POS writes, a sync service, and a visible
offline/online indicator.

### H2. Biometric / device login

PumpPOS ships biometric login for shared counter devices.

**New:** optional device-bound login and PIN-only quick-login with audit.

### H3. Multi-site / head office

DigitalManager: "Head Office and Multiple Filling Stations management." Our
`branches` gives the data model, but there is no consolidated HO view.

**New:** a head-office role, consolidated multi-branch dashboards, and inter-branch
stock transfer.

---

## I. PROFIT CORRECTNESS — refine

Umaish: "The system **separates normal evaporation from meter drift and from
genuine shortage** by comparing patterns across shifts and tanks, so a failing
meter or a leaking line shows up as a trend rather than a one-off argument."

Our variance report is per-reading. A trend view is what actually catches theft.

**New:** variance trend analysis per tank and per nozzle, evaporation baseline,
and an anomaly flag when a tank's variance repeats.

---

## Summary of additions

| Area | New capability |
|---|---|
| Compliance | FBR digital invoicing, QR, Rs.1 PoS fee, fiscal number, licensed-integrator interface |
| Tax | Provincial tax authorities (PRA/SRB/KPRA/BRA), withholding tax |
| Fuel supply | OMC khata, cheque tracking, tanker deliveries, density/wet-stock |
| Correctness | Mid-shift price change splitting |
| Revenue | Non-fuel retail (lubricants, tyres, car wash, TUC, shop) with barcode POS |
| Staff | Advances, loans, payroll |
| Cash | Denomination breakdown, test returns |
| Comms | WhatsApp/SMS channels |
| Reliability | Offline sync, biometric, head-office consolidation |
| Analytics | Variance trends, evaporation baseline |

All are **additive**. No section of the original spec is dropped.
