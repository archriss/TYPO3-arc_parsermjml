# Archriss MJML Parser #

## This extension able user to convert mjml file into html using style mjml file ##

You can splice your mjml file into parts, each part will be converted into html.

Usefull for converting easily fluid mjml file partial into html for direct usage. 

Warning : do not handle mj-include

## How it work

- Copy your mjml partial into a fileadmin folder (or a new file mountpoint)
- Use MJML module to navigate to this directory
- Use simple fluid variables (not tested yet with fluid tags)
- Select the style file and convert (existing files can be overrided)

Remember to request appId + public/secret key on mjml.io
