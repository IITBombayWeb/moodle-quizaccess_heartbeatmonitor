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
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function client(Y, quizid, userid, username, attemptid, sessionkey, hbcfg)
{
//	var obj = JSON.parse(cfg);
	var nodecfg = JSON.parse(hbcfg);
//	console.log(obj);
//	var socket = io('http://127.0.0.1:3000', {
	var socket = io(nodecfg.wwwroot + ':' + nodecfg.port, {
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
	if(!attemptid)	{	
	var roomid = username + '_' + quizid + '_' + attemptid;	
	
	socket.on('connect', function() {
		console.log('-- In client \'connect\' event --');
		console.log('-- After socket connected - ' + socket.id  + '. Curr. TS - ' + Math.floor(new Date().getTime()/1000));
		socket.emit('attempt', { username:username, quizid:quizid, roomid:roomid, attemptid:attemptid });
		
	});	
	
	socket.on('timeserver', function(data) {
		console.log('-- In timeserver event --');
		console.log('-- Curr. Timeserver id - ' + data.currenttimeserverid);
	});
}	
	socket.on('disconnect', function() {
		console.log('-- In client \'disconnect\' event --');
		console.log('-- After socket disconnected - ' + socket.id  + '. Curr. TS - ' + Math.floor(new Date().getTime())/1000);
	});

}

