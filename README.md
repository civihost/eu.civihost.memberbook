# Member Book (WIP)
Italian Associations and Co-operatives must print the Register of Members (_Libro Soci_ in italian and hereinafter referred to _Member Book_) at the end of each year or at the request of a public authority (e.g. in case of an inspection), or because a bank asks for a copy, or in order to participate in a announcement for contributions or soft loans.

The Member Book must contain the contact data of each member: first and last name if it is an individual, organization name for organizations, tax/ssn code, birth date and address.  
Then it is necessary to indicate the member code, the date of admission (normally the _join date_ of the membership), the membership fee subscribed and paid for the year (Associations) or for the entire period (Co-operatives).

The membership fee has a different meaning depending on the type of organisation:
- for **Associations**: is an annual fee and cannot be refunded. The membership duration is one year;
- for **Co-operatives**: is a share like for capital companies. Members can subscribe new shares and request reimbursement of part of it. The membership duration is _Litetime_.

There are also differences in relation to the Member Code:
- for **Associations**: must be regenerated every year, a sequential number according to the date of admission. In this extension adopted the compromise to use the `receipt_date` as admission date. Then we recommend to create a custom field for Contribution to save the Member Code;
- for **Co-operatives**: the Member Code follows the membership, so we recommend  to create a custom field for Membership to save it.

This extension creates two Member report templates:

### 1. MemberbookMembers

Show members detail with these additional columns:
- **Total subscribed**: the total amount of contributions for the membership. 
- **Total paid**: the total amount paid for the membership. It considers reimbursements.

Some column headings are rewritten by extension settings.

Then we add these filters:
- **Year** (`active_in_year`): shows only memberships active for the specified year (check in `start_date` and `end_date` fields).

### 2. MemberbookContributions
Show the membership contributions with these additional columns:
- **Total subscribed**: the total amount of contributions for the membership.  Please note that if in extension settings you choose the flag "Considers only contributions for one year", this is the amount of contribution.
- **Total paid**: the total amount paid for the membership. It considers reimbursements. Please note that if in extension settings you choose the flag "Considers only contributions for one year", this is the total amount paid for the contribution.

Some column headings are rewritten by extension settings.

Then we add these filters:
- **Year** (`active_in_year`): shows only memberships active for the specified year (check in `start_date` and `end_date` fields).
- 
**This report is useful for Associations and is the only one needed to print the Member Book** because we assume that the membership fee is only one per membership per year.

A **custom template** is defined for each report to **customize print action** (`$outputMode == 'pdf'`) because they must have a formatting without header and footer.

This extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

- PHP v7.4+
- CiviCRM 5.59+

## Installation

Install as a regular CiviCRM extension.

## Usage

The extension adds a new settings menu under Administer > CiviMember > Memberbook Settings.

![image](https://github.com/user-attachments/assets/10c5a8e7-a685-43f4-a757-6c0692554396)


Then you need to go to Administer/CiviReport/Create Reports from template to create a report.

## Known Issues

https://github.com/civihost/eu.civihost.memberbook/issues


## Support

Please post bug reports in the issue tracker of this project on GitHub: https://github.com/civihost/eu.civihost.memberbook/issues

While we do our best to provide free community support for this extension,
please consider financially contributing to support or development of this
extension.

This is mantained by Samuele Masetto from [CiviHOST](https://www.civihost.it) who you can contact for help, support and further development.

## Disclaimer

This is still a work-in-progress extension.

