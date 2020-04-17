Profile-based roles (tool_profileroles)
=======================================

![Picture of profiteroles](profiteroles.jpg)

(It says profileroles, not profiteroles.) 

Intended use case
-----------------

Sometimes you want a role at a system level that identifies a certain type of user
across the system. For example, we use a *sitewide tutor* role that gives tutors
limited permissions across the whole system, even though they only have the actual
tutor role in specific courses.

This plugin lets you automatically allocate roles based on fields in the user
record. Typically this would be fields set by your authentication system (e.g.
the auth_saml2 plugin) rather than ones the user can change themselves.

Features
--------

* You can assign roles based on any field from the main user record (e.g. 
  department), or any custom profile field.
* All fields that are included in the user record can be used. This mainly excludes
  unlimited-length (text) custom fields.
* You can assign roles based on an exact match to a field value, or a regular 
  expression pattern.
* Roles will be removed if the user does not match the condition.   
* Multiple different fields or field values can grant a single role (OR conditions).

Limitations
-----------

* This plugin assigns roles only in the system context, not in individual courses.
  (You should use an enrol plugin for that.)
* When using multiple fields to grant a role, AND conditions are not supported.
* This plugin only runs when a user logs in. If something happens to change a
  user's field values, it will not take effect until their next login.

Usage
-----

The settings screen can be found under Plugins / Admin tools / Profile-based roles.
There are only two options:

1. An Enabled checkbox lets you enable or disable this plugin. If not enabled, it
  will neither add nor remove any roles.
2. A text area lets you specify the role configuration.

Role configuration
------------------

The role configuration is a list of roles managed by this plugin. Each line
refers to one role.

* In real life, you would normally use this system with roles that you created
  specially. The examples below use the standard Manager role for simplicity.

The simplest format is like this:

    manager: department=IT
    
This means that every user who logs in will have the manager role added if they
are in the IT department (and do not already have the role), and will have the 
manager role removed if they are not in the IT department (and have the role).

* The word *manager* is the short name of the Manager role. (Check the define 
  roles screen if you need to see what the short name of any role is.)
* It must be followed by a colon.
* The word *department* is the short name of any user field. This can be a standard 
  field (as in this case) or a custom field.
* The = symbol means that it must be an exact match.
* *IT* is arbitrary text that will be matched against the value of the department field.
  The match is case-sensitive.

The plugin ignores whitespace except in the middle of text, so if you prefer spaces 
around the equals signs, go crazy with them.

It is possible to have multiple conditions:

    manager: department=IT, lastname=Marshall
    
This means that if the user is in the IT department *or* if they have the last name 
Marshall, or both, they will be given the Manager role.

* Because commas are used to separate clauses, you cannot include commas inside the 
values being checked.

Finally, you can use regular expression matches:

    manager: department ~ ^(IT|Maths?)$

This means that the department field will be checked against the regular expression
/^(IT|Maths?)$/, so it will match if the department is IT, Maths, or Math. 

* The surrounding / symbols are added automatically.

This is evaluated as a PHP regular expression, so please use online tools to check 
your expression is valid.

For less advanced use, if you include only letters and numbers and no special 
characters, the ~ operator will behave the same as a simple 'contains' check.
For example, *department ~ Math* would match department names *Mathematics* or
*Mathematical Engineering* or *Computer Science & Mathematics*. 
    
Error handling
--------------

If you get anything wrong in the role configuration (for example, you type a line
incorrectly, you do something that would make it try to add a role that doesn't
exist, you mention the same role more than once, or a regular expression is 
invalid) then you will see debugging messages when a user logs in who would be
affected by that configuration.

* If the debugging setting is off, then you won't see these errors.
* If debugging is on but is set to save to the error log, these errors will
  appear in the log.

After configuring this system, you should test with affected user accounts to
check it behaves correctly.

* If there is an error with a given role line then generally, the plugin will 
  neither add nor remove that role from anyone.
  
Logging
-------

This plugin does not log anything extra when users have a role assigned or removed. 
However, Moodle has standard logging for both these cases, which still applies.  

Credits
-------

Originally developed by sam marshall for The Open University.

Profiteroles image credit: Andrew Knowles (CC BY 2.0)

