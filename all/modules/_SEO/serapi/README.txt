$Id: README.txt,v 1.2 2010/10/13 11:43:29 yaph Exp $
Drupal Serapi README
---------------------------------
Author: Ramiro Gómez (http://www.ramiro.org)

This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
(see LICENSE.txt)

Requirements:
-------------
This module requires Drupal 7.X.

Overview:
---------
Retrieve information on search queries for users that reached your site via a
search engine.

Installation:
-------------
Extract the download package in your contributed modules directory and 
then enable the module in 'admin/build/modules/'.

Usage:
------
Serapi provides the function serapi_get_search() to get information, whether
the current request comes from one of the supported search engines and which
search query the user entered to reach your site.
