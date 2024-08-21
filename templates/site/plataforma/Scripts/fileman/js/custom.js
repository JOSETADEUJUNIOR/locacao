/*
  RoxyFileman - web based file manager. Ready to use with CKEditor, TinyMCE. 
  Can be easily integrated with any other WYSIWYG editor or CMS.

  Copyright (C) 2013, RoxyFileman.com - Lyubomir Arsov. All rights reserved.
  For licensing, see LICENSE.txt or http://RoxyFileman.com/license

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

  Contact: Lyubomir Arsov, liubo (at) web-lobby.com
*/
function FileSelected(file) {

    debugger;

    var inputImg = RoxyUtils.GetUrlParam('InputImg');
    var input = RoxyUtils.GetUrlParam('Input');
    var popup = RoxyUtils.GetUrlParam('Popup');

    //alert(input);

    //if (fieldId == "file") {
    //    $(window.parent.document).find('#file').attr('value', file.fullPath);
    //    //(window.parent.document).find('#RedirectFile').attr('src', file.fullPath);
    //    window.parent.closeCustomRoxyFile();
    //} else {

    if (inputImg != "") {
        $(window.parent.document).find('#' + inputImg).attr('src', file.fullPath);
    }

    $(window.parent.document).find('#' + input).attr('value', file.fullPath);

    if (popup == "1") {
        window.parent.closeCustomRoxy_1();
    } else if (popup == "2") {
        window.parent.closeCustomRoxy_2();
    } else if (popup == "3") {
        window.parent.closeCustomRoxy_3();
    } else if (popup == "4") {
        window.parent.closeCustomRoxy_4();
    } else if (popup == "5") {
        window.parent.closeCustomRoxy_5();
    }

    //}
}
function GetSelectedValue() {
    /**
    * This function is called to retrieve selected value when custom integration is used.
    * Url parameter selected will override this value.
    */

    return "";
}
