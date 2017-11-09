/**
 * Client script for the quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess_heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017, Indian Institute Of Technology, Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//-----------------Import a socket.io.js file here - not working!--------------------------

//var imported = document.createElement('script');
//imported.src = 'http://127.0.0.1:3000/socket.io/socket.io.js';
//document.head.appendChild(imported);
//
//$.getScript('http://127.0.0.1:3000/socket.io/socket.io.js', function() {
//	// script is now loaded and executed.
//    // put your dependent JS here.
//
//	var socket = io.connect('http://127.0.0.1:3000');
//	alert('socket connected');
//});

//-------------------------------------------------------------------------------

//$(function(){		//-------------------using jquery------------------------------------
	// connect to the socket
	var socket = io('http://127.0.0.1:3000');
	
//	socket.on('connect', function(){
//		socket.emit('load', id);		
//		console.log("connected - client side");
//	});
//});