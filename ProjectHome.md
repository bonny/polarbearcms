# PolarBear CMS #

PolarBear CMS is a Content Management System used and developed by the nice people at MarsApril. Since it has developed into a pretty nice and usable system we thought it would be a shame to not share this with the world. So here you have it. Hope you enjoy it as much as we do! :)

## Update October 2010 ##
I'm not 100 % sure, but I think there will be no more updates to this project. It was a fun ride, but I've moved several core components of PolarBear into [WordPress plugins](http://profiles.wordpress.org/users/eskapism/#main-plugins) instead. That way I can reach a wider audience.

The plugins for WordPress based on PolarBear CMS ideas/components are:
  * [CMS Tree Page View](http://wordpress.org/extend/plugins/cms-tree-page-view/)
  * [Simple Fields](http://wordpress.org/extend/plugins/simple-fields/)
  * [Simple Thumbs](http://wordpress.org/extend/plugins/simple-thumbs/)
  * [Simple History](http://wordpress.org/extend/plugins/simple-history/)
  * [Admin Menu Tree Page View](http://wordpress.org/extend/plugins/admin-menu-tree-page-view/)
  * [Simple Seo](http://wordpress.org/extend/plugins/simple-seo/)

If you use WordPress as a CMS system you should really check these plugins out.

## Why yet another CMS ##
We missed some things in for example Wordpress and the options were to extend Wordpress with plugins to suit our needs, or to develop our own system with full control of all aspects. We went with full control.

## Some features that we like and that makes the system uniqe ##

  * Any article in the CMS can have **three different "titles"**: one title that is shown in the browsers title-bar (the "page title"), one title that is shown in navigation areas (the "nav title") and one title that is shown on the actual page (the "article title"). This makes it possible to for example have a meny with the title "About Company XYZ" and then on the article have a headline with the title "Company XYZ is a leader in something-super-cool".

  * Tree based structure with **drag and drop-support** for easy modifing of the structure of a site.

  * Built in support for **meta description and meta keywords**.

  * Supports **hierarchical tags**, i.e. a tag can be a child of another tag. Gives you great flexibility!

  * Uploaded **files can be organized using tags**

  * Breadcrumb-like URLs. An URL is not only something you use to navigate to a page. An URL is also used to give you information about the page; without even visiting a URL you should be able to see it in a context. Instead of a regular URL like company.com/peter/ you will have a URL that look like company.com/about-us/employees/peter/. This is better because it cleary shows that the current page is a sub-page of employees, which is a sub page of about us. An experienced user can easily just remove /peter/ to view all employees.
  * **Self redirecting URL:s**: say you have an article at example.com/users/peter and then move it so it becomes example.com/staff/peter. In other systems, going to the first URL would result in a 404. In PolarBear CMS? No problem, you will be directed with a 302 to the new adress. As long as the last section of the URL has the same name as before, you're safe

  * Read [Jacob Nielsen's article about "URL as UI"](http://www.useit.com/alertbox/990321.html) for more great thoughts about URLs.

  * Fields: probably the **most cool function**. Normaly an article constists of a title, a teaser and a body. But what happens if you want to attach files? Or add several images as a slideshow? Or mybe a related content-box or a drop-down where you can select if this article should have a add-this-function enabled? This is where fields kick ass; to any article on the site you can customize what fields that should be available. A field can be a textbox, a textarea, a WYSIWYG-editor, a file/image-chooser or a drop-down menu. This gives you unimaginable powers and fields is pretty much what spider man and superman uses when they develop web sites.

  * Uses the great **templating system Dwoo** to control output.