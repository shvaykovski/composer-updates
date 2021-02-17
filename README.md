Composer packages updates report
=========

The tool for analyzing outdated Composer packages on your project.

### Requirements

- **PHP 7.2+**

### Make a report

Run:
```bash
/usr/bin/php -f report.php <PROJECT_PATH> > reports/report.html
   ```

### Compare 2 reports

Run:
```bash
/usr/bin/php -f compare.php reports/report1.html reports/report2.html > reports/compare.html
   ```
