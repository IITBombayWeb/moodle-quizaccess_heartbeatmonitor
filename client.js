// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Client script for quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//$(function(){		//-------------------using jquery------------------------------------
	// connect to the socket
//	var socket = io('http://127.0.0.1:3000');
	
//	socket.on('connect', function(){
//		socket.emit('load', id);		
//		console.log("connected - client side");
//	});
//});
//-------------------------------------------------------------------------------------

//var socket = io('http://127.0.0.1:3000');

//------------------------------ using YUI --------------------------------------------
function client(Y, quizid, userid, username, attemptid, sessionkey)
{
	var socket = io('http://127.0.0.1:3000');	
	var roomid = username + quizid;	
	socket.on('connect', function() {
//		console.log("Connect function - client side");
		socket.emit('attempt', { username:username, quizid:quizid, roomid:roomid, attemptid:attemptid });
	});	
}

