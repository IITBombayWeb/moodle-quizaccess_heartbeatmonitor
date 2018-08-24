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
 * Server script for quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//function testnode(Y)
//{
// function countDownTimer() {				
////	$(document).ready(function() {
//		var interval = setInterval(function() {
//			var currentTime = new Date().getTime();
//           				 	
//			while (true) {
//				clearInterval(interval);
//				$('#timer').hide();
//			    $('#startAttemptButton').show();

				var http = require("http");
				http.get({host:"127.0.0.1", port:3000}, function(res){
					console.log("-- here --");
					if(res.statusCode == 200)
						console.log("Up n running");
					else
						console.log("Down");
				})
				
//			}
//    
//		}, 1000);
////	});
//}
//}
			

//var app 		= require('express')();
//var http 		= require('http').listen(app);
//var io			= require('socket.io').listen(http);
//var bodyParser 	= require('body-parser');
//var mysql 		= require('mysql');
//var fs 			= require('fs');
//var obj;
//
//
//// EXEC SYNC - WORKS
////var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("../../../../config.php"); print json_encode($CFG);\'');
//var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("/var/www/html/moodle/config.php"); print json_encode($CFG);\'');
//obj = JSON.parse(child);
//
//// DB CONN
////console.log('-- Connecting to the db --');
////console.log(obj.dbhost);
////console.log(obj.dbuser);
////console.log(obj.dbpass);
////console.log(obj.dbname);
//var con = mysql.createConnection({
//	host 	 : obj.dbhost,
//	user 	 : obj.dbuser,
//	password : obj.dbpass,
//	database : obj.dbname
//});
//
////var con = mysql.createConnection({
////	host 	 : 'localhost',
////	user 	 : 'moodle-owner',
////	password : 'Moodle@123',
////	database : 'moodle'
////});
//
//var port = 3000;
//
////console.log(http);
//http.on('listening',function(){
//    console.log('-- Ok, server is running --');
//});
////
////http.on('connection', function(socket) {
////    socket.on('data', function(buf) {
////        console.log('received',buf.toString('utf8'));
////    });
////});
//
//http.listen(port, function() {
//	console.log('-- Listening on port ' + port + ' --');
//});







