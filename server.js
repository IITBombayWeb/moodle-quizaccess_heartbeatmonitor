/**
 * Server script for the quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess_heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017, Indian Institute Of Technology, Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var app 		 = require('express')();
var http 		 = require('http').createServer(app);
var io			 = require('socket.io').listen(http);

console.log('In server.js');

var port = 3000;
var count = 0;
var sockarr = [];

var record = io.sockets.on('connection', function (socket) {	
	// When the client emits the 'load' event, reply with the 
	// number of people in this chat room
	count++;
	sockarr[count] = socket;
	console.log(count + ' - ' + socket.id + ' socket connected');
	
	socket.on('disconnect', function(){
		for (const [key, value] of Object.entries(sockarr)) {
			if (value === socket) {
				var index = key;
			}
		}
		console.log(index + ' - ' + socket.id + ' socket disconnected');
	});
});

http.listen(port, function(){
	console.log('Listening on port ' + port);
});