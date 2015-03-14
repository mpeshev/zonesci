ZonesCI is small and easily extensible auth library for the CodeIgniter framework. As there is no inner auth module to help handling users and groups, ZonesCI defines some simple rules to control user/groups behavior.

Top ZonesCI features:

  * easy to add to existing project with users or groups tables
  * no need to edit any super classes or alter any existing database data
  * easy to extend
  * no useless functionality

ZonesCI defines two terms:
  * users
  * groups

Certainly, users define the 'user' entity. You could use a custom USERS table in database and configure it with 2 simple parameters so that you can use it here.

Groups are roles identities - 'admin', 'member', 'tester' etc. Each user could attend 0,  1, 2 or more roles. That makes adding and removing permissions easier and skips the foreign key alteration for existing tables.