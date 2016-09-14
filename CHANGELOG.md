# Change Log
Only partial changelog, [commit history](https://framagit.org/Shnoulle/LimeSurvey/commits/2.06_SondagesPro) show all changelog.

## unreleased
- Fixed issue: ~ and _ in tokens hard to manually enter

### Feature
- Allow plugin to add new question attributes

### Fix
- Fixed issue #11509: numerical input option integer only leads to positive integer input only

## [1.0.23] - 2016-07-17

### Feature
- Allow plugin to set more option when using renderHtml

### Fix
- Language is not correct when session timed out  or other error page
- Allow plugin to allow preview question or group without admin rights

## [1.0.22] - 2016-07-05

### Feature
- Log error and warning in tmp/runtime/application.log by default
- Allow plugin to set more option when using renderHtml

### Fix
- Improvement on error when DB save for public survey
- Fix DECIMAL value before try to save

## [1.0.21] - 2016-06-17

### Feature
- Allow to set column number on ranking question

### Fix
- EM tips are empty after reloading page via browser (F5)
- Ranking question : Alert are not show every time
- Ranking question : Add answers can broke Survey DB

## [1.0.20] - 2016-06-16

### Feature
- afterSurveyMenuLoad event to add survey specific menu items

### Fix
- Improvement on Conditions page with array (number) questions
- Google Analytics code not running

## [1.0.19] - 2016-06-09

### Fix
- Better loading of plugins for command
- Default email format in survey to html

## [1.0.17] - 2016-05-19

### Feature
- Add cssclass question attribute

### Fix
- Plugin survey setting type "checkbox" does not properly save
- Map question : google.maps fix for hidden text element
- Numeric comparaison with Expression
- Survey response marked as 'finished' after opening email link/password twice
- Unable to export result as PDF
- PHP memory_limit being set too low
- Bad link for Browse uploaded ressources if publicurl is set

### Updated
- New token table firstname/lastname to 150

## [1.0.9] - 2016-04-22

### Feature
- beforeController event plugin (for web)
- newUnsecureRequest : plugin direct request without CRSF

### Fix
- Checked responses are not read when load "surveys uploaded file"
- Using mktime() EM function broke survey administration
- EM regexMatch function don't show pattern error
- Attachments for registration emails don't get attached
- Remote control list_surveys can list whole surveys, and not only needed
- event beforeTokenEmail is not dispatched for register
- thousand separator break slider in some condition

## [1.0.6] - 2016-03-20

### Fix
- Issue with relevance on X scale with multilingual
- Languages can not be updated in label set administration
- Error with SMTP mail method
- Broken HTML or script can broke Survey Logic File
- beforeHasPermission event don't happen for owner of survey
- [Security] Survey ID not properly sanitized on survey creation
- 4-byte UTF characters (e.g. Emojis) entered into text can cause DB issue (mysql)
- [Security] issue when saving/loading responses on public survey

## [1.0.0] - 2016-03-01

### Fix
- Higher risk that the emails are rated as Spam
- Filter script in Plugin management and Survey Logic file.

### Updated
- Use updatable from config and use it, set to updatable=false

Start with LimeSurvey 2.06lts
