# Add date to file names from Photos.app

There are some issues with the file dates when exporting from Photos.app, the default photo manager in Mac OS:

* The default export mode always creates new files, setting the creation date to now. 
* The export original mode preserves the date, but used the date the file was added to the library, which can
differ from the real creation date.

The only way to review the creation date is opening the file in a media player or editor to view the 
meta data in the video streams or the EXIF tags.

This script renames photos or videos to include the real creation date in the filename. Customize to your 
own needs.