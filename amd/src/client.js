/**
 * Client script for the quizaccess_heartbeatmonitor plugin.
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

var socket = io('http://127.0.0.1:3000');