Subject: Re: Account Suspension - tamincom - Remediation Complete & Backup Access Request

Dear Namecheap Legal & Abuse Team,

Thank you for your notice dated April 27, 2026 regarding the suspension
of hosting account "tamincom" (domain: daliltameni.online) under
Section 8 of the Acceptable Use Policy.

After reviewing my own codebase I have identified and removed the
content that most likely triggered your automated phishing/abuse
classifier. To clarify the nature of the flagged files and confirm
remediation:

WHAT WAS IDENTIFIED
-------------------
The following PHP files were present under public_html/ as one-time
deployment helpers used during initial setup of my Laravel application:

  - acme-helper.php       (ACME HTTP-01 challenge writer for SSL)
  - setup-ins2026.php     (one-time installer)
  - fix-ins2026.php       (cache rebuild after install)
  - mk-symlink.php        (storage symlink creation)
  - run-seeder.php        (database seeding)
  - create-admin.php      (initial admin account creation)
  - show-log.php          (read-only Laravel log viewer)
  - cleanup.php           (self-deleting cleanup script)

These were NOT phishing pages and were never linked from the public
site. They were token-protected administrative scripts intended for
single use during deployment. I now understand that this pattern -
PHP files in the document root that execute privileged actions via
URL parameters - matches the signature commonly used by malicious
backdoors/web shells, which is almost certainly why your automated
scanner flagged the account.

REMEDIATION TAKEN
-----------------
1. All eight files listed above have been permanently deleted from
   my source repository (commit 73f5127).
2. .gitignore has been hardened to block any future commit of
   public/*setup*.php, public/*fix*.php, public/cleanup.php,
   public/create-admin.php, public/run-seeder.php, public/show-log.php,
   public/acme-helper.php, public/mk-symlink.php.
3. All credentials, SSL keys and API tokens that were referenced by
   the removed scripts have been rotated.
4. Going forward, all such administrative actions will be performed
   exclusively via authenticated SSH + Laravel artisan CLI - never
   via web-accessible PHP files.

I take full responsibility for creating these scripts. They were
poor deployment practice and I accept that the account suspension
was a reasonable automated response under your AUP. The site itself
(daliltameni.online) is a legitimate Saudi car-insurance comparison
project, not a phishing or fraudulent service.

REQUEST
-------
I am NOT contesting the suspension and I do NOT intend to continue
hosting this project on Namecheap. I am migrating to a different
provider better suited to a Laravel + WebSocket workload.

What I do respectfully request is short-term, READ-ONLY access for
the sole purpose of retrieving:

  - A full cPanel backup (or SFTP read-only access) so I can recover
    the MySQL database (insurance2026) and any user-uploaded files
    under storage/app/.
  - This is critical because customer records and audit logs cannot
    be reconstructed from my git repository alone.

I am happy to provide:
  - Government ID for account ownership verification
  - Saudi commercial registration if helpful
  - Any further information your team requires

After backup retrieval I will not request reactivation. The account
can be terminated per your normal procedure once I confirm receipt
of the backup.

Thank you for your time and for the explanation in your initial
notice. I will not dispute the AUP determination - I am asking
only for a brief data-recovery window before account closure.

Account: tamincom
Domain:  daliltameni.online
Reference: [INSERT TICKET NUMBER FROM THEIR EMAIL]

Best regards,
[Your full legal name]
[Your email on file with Namecheap]
[Your phone number]
[Saudi commercial registration number, if applicable]
