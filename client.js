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
	var socket = io('http://127.0.0.1:3000', {
						'secure':                    false,
					    'connect timeout':           5000,
					    'try multiple transports':   true,
					    'reconnect':                 true,
					    'reconnection delay':        500,
					    'reopen delay':              3000,
					    'max reconnection attempts': 10,
					    'sync disconnect on unload': true,
					    'auto connect':              false,
					    'remember transport':        false,
					    transports: [
					        'websocket'
					      , 'flashsocket'
					      , 'htmlfile'
					      , 'xhr-multipart'
					      , 'xhr-polling'
					      , 'jsonp-polling']
					});	
	
	var roomid = username + quizid;	
//	console.log('before socket connected');// + socket);
//	for (var i in socket){
//		console.log(socket[i].id);
//	}
//	console.log('before socket connected' + socket.id + ' ts ' + (socket.handshake.issued) + 
//	console.log(' cur ' + (new Date().getTime()));
	
	socket.on('connect', function() {
		console.log('after  socket connected ' + socket.id  + ' cur ' + (new Date().getTime()));
//		for (var i in socket){
//			console.log(socket[i].id);
//		}
//		console.log(' ts ' + (socket.handshake.issued) + ' cur ' + (new Date().getTime()));

//		console.log('connect emit recieved' + socketi );
//		console.log("Connect function - client side");
		socket.emit('attempt', { username:username, quizid:quizid, roomid:roomid, attemptid:attemptid });
	});	
	
	socket.on('disconnect', function() {
		console.log('after  socket disconnected ' + socket.id  + ' cur ' + (new Date().getTime()));
	});
}

