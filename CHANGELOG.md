# Change Log
All notable changes to this project will be documented in this file.

## [5.1] - 2023-06-08

### Added
- Added changelog file
- Added template to templatelist to load

### Changed
- Use MyBB datacache to store icons instead of a static variable.
- Removed check for forum_icons table existance on admin page
- Reduced queries on ACP forum icons list page

### Fixed
- PHP warnings - Thanks to @SvePu
