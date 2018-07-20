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

var app 		= require('express')();
var http 		= require('http').createServer(app);
var io			= require('socket.io').listen(http);
var bodyParser 	= require('body-parser');
var mysql 		= require('mysql');
var fs 			= require('fs');
var obj;


// EXEC SYNC - WORKS
var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("../../../../config.php"); print json_encode($CFG);\'');
obj = JSON.parse(child);

// DB CONN
console.log('-- Connecting to the db --');
console.log(obj.dbuser);
var con = mysql.createConnection({
	host 	 : obj.dbhost,
	user 	 : obj.dbuser,
	password : obj.dbpass,
	database : obj.dbname
});

app.use(bodyParser.urlencoded({ extended: true }));

var port = 3000;
var totalconnectedsockets;
var roomwisesockets;
var roomwisesocketids = [];
var allsocketscount;
var allsocketids;
var cfg;

var record = io.sockets.on('connection', function (socket) {	
	console.log('In \'connect\' event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
	allsocketscount = io.sockets.server.eio.clientsCount;
	console.log('Socket connected - ' + socket.id + '. TS - ' + (socket.handshake.issued) + '. Curr. TS - ' + (new Date().getTime()));
	
	var allclients = io.sockets.server.eio.clients;
	allsocketids = [];
	for (var clientid in allclients) {
		// This is the socket of each client in the room.
		allsocketids.push(io.sockets.server.eio.clients[clientid].id);
	}  
	
	socket.on('attempt', function(data) {
		console.log('-- In attempt event --');
		
		// Append some extra data to the socket object.
	    socket.username 		= "'" + data.username + "'";
	    socket.quizid 			= data.quizid;
        socket.roomid 			= "'" + data.roomid + "'";
        socket.socketid 		= "'" + socket.id + "'";
        socket.statusConnected 	= "'Connected'";
        socket.timestampC 		= socket.handshake.issued;
        socket.ip 				= "'" + socket.request.connection.remoteAddress + "'";
        cfg = data.config;

        var sql = "INSERT INTO mdl_quizaccess_hbmon_socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" +
         			"(" + socket.username + "," 
         				+ socket.quizid + "," 
         				+ socket.roomid + "," 
         				+ socket.socketid + "," 
         				+ socket.statusConnected + "," 
         				+ socket.ip + "," 
         				+ socket.timestampC + ")";
	    con.query(sql, function(err, result) {
	    	if (err) throw err;
		});
	    console.log('-- Insert done --');

	    // Join the connected socket to the room. 'roomid' is the concatenaton of (username + quizid).
	    // Here, 'roomid' corresponds to a particular user.
	    socket.join(socket.roomid);	   
	    
	    // Find the total connected sockets in the room.
	    roomwisesockets = io.sockets.adapter.rooms[socket.roomid].sockets;
	    roomwisesocketids[socket.roomid] = [];
	    for (var clientid in roomwisesockets) {
			// This is the socket of each client in the room.
			var clientsocket = io.sockets.connected[clientid];
			roomwisesocketids[socket.roomid].push(clientsocket.id);
		}
	   
	    totalconnectedsockets = io.sockets.adapter.rooms[socket.roomid].length;   

	    if (totalconnectedsockets > 0) {
	    	socket.currentstatus = "'Live'";
           
	    	// 'livetable1' reflects current status of a user. There is one entry per user in this table. 
	    	// If exists, fetch previous entry for this user from 'livetable1'.
	    	
	    	var liverecordexistsql = "SELECT * FROM mdl_quizaccess_hbmon_livetable1 WHERE roomid = " + socket.roomid;
	    	con.query(liverecordexistsql, function(err, result) {
                if (err) throw err;                
                if (result.length > 0) {
                    for (i in result) {
                    	// Previous state details.
                        var status 			= result[i].status;
                        var timetoconsider 	= result[i].timetoconsider;
                        var deadtime 		= result[i].deadtime;
                        var livetime 		= result[i].livetime;
//                        console.log(socket.roomid + ' - Previous deadtime - ' + humanise(deadtime) + ' - Status - ' + status);
                    }
                    if (status == 'Dead') {
//                        console.log(socket.roomid + ' - Current deadtime is - ' + humanise(socket.timestampC - timetoconsider));
                        
                        // Check whether it is a ques switch or not. Ques switch time is approx. betwn. 0-2 secs.
                        // This is required when there is only one connected socket for that user.
                    	
//                        if((socket.timestampC - timetoconsider) > 2000) {
	                        // Compute cumulative deadtime.
	                        deadtime = parseInt(deadtime) + parseInt(socket.timestampC - timetoconsider);
//	                        console.log(socket.roomid + ' - After cumulation, deadtime is - ' + humanise(deadtime));
	
	                        // Update 'status' entry for this user in 'livetable1'.
	                        console.log('-- Updating live status --');
	                        console.log('-- Curr. status -- ' + socket.currentstatus);
	                        var updatelivetablesql = "UPDATE mdl_quizaccess_hbmon_livetable1 SET status = " 	+ socket.currentstatus 
	                        								+ ", deadtime = "  	+ deadtime 
	                        								+ ", timetoconsider = " + socket.timestampC 
	                        								+ " where roomid = " + socket.roomid;
	                        con.query(updatelivetablesql, function(err, result) {
	                            if (err) throw err;
	                        });
	                        console.log('-- Curr. TS --' + (new Date().getTime()));
//                        } else {	
//                        	// Add ques switch time to the live time.
//                        	// Compute cumulative livetime.
//            		    	livetime = parseInt(livetime) + parseInt(socket.timestampC - timetoconsider);
//            		    	console.log(socket.roomid + ' - After cumulation, livetime  is - ' + humanise(livetime));
//            			
//            		    	// Update 'status' entry for this user in 'livetable1'.
//            		    	var updatelivetablesql = "UPDATE livetable1 SET status = " + socket.currentstatus 
//            		    									+ ", timetoconsider = " + socket.timestampC 
//            		    									+ ", livetime = " + livetime 
//            		    									+ " where roomid = " + socket.roomid;
//            			    con.query(updatelivetablesql, function(err, result) {
//            			    	if (err) throw err;
//            			    });
//                        }
                    }
                } else {
                	// Insert current status entry for this user in 'livetable1'.                	
                    var livetablesql = "INSERT INTO mdl_quizaccess_hbmon_livetable1 (roomid, status, timetoconsider, livetime, deadtime) VALUES" +
                                      	"(" + socket.roomid + "," 
                                      		+ socket.currentstatus + "," 
                                      		+ socket.timestampC + ", 0, 0 )";
                    con.query(livetablesql, function(err, result) {
                        if (err) throw err;
                    });
                }
            });
	    }
	});
	
	
	socket.on('disconnect', function() {
		console.log(socket.id + ' disconnected. Curr. TS - ' + (new Date().getTime()));
		console.log('In \'disconnect\' event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
		
		allsocketscount = io.sockets.server.eio.clientsCount;
		var allclients = io.sockets.server.eio.clients;
		allsocketids = [];
		if (allclients != undefined) {
			for (var clientid in allclients) {
				// This is the socket of each client in the room.
				allsocketids.push(io.sockets.server.eio.clients[clientid].id);
			} 
		}
	
		if(socket.roomid != undefined) {			
			// Construct record for the disconnected socket.
			socket.timestampD = new Date().getTime();
			socket.statusDisconnected = "'Disconnected'";
			
		    // Insert disconnection record into database.
			var sql = "INSERT INTO mdl_quizaccess_hbmon_socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" +
					  	"(" + socket.username + "," 
					  		+ socket.quizid + "," 
					  		+ socket.roomid + "," 
					  		+ socket.socketid + "," 
					  		+ socket.statusDisconnected + "," 
					  		+ socket.ip + "," 
					  		+ socket.timestampD + ")";
		    con.query(sql, function(err, result) {
		    	if (err) throw err;	  
		    });
		    
		    // Find the total connected sockets in the room.
	        var rooms = io.sockets.adapter.rooms[socket.roomid]; 
	      
	        if(typeof rooms != 'undefined') {
	  	    	totalconnectedsockets = io.sockets.adapter.rooms[socket.roomid].length;  
	  	    	
	  	    	// Find the total connected sockets in the room.
	  		    roomwisesockets = io.sockets.adapter.rooms[socket.roomid].sockets; 
	  	    	roomwisesocketids[socket.roomid] = [];
	  		    for (var clientid in roomwisesockets) {
	  				// This is the socket of each client in the room.
	  				var clientsocket = io.sockets.connected[clientid];
	  				roomwisesocketids[socket.roomid].push(clientsocket.id);
	  			}
	  	    } else {
	  	    	totalconnectedsockets = 0;
	  	    }
       	    
	  	    if(totalconnectedsockets == 0) {          
			    socket.currentstatus = "'Dead'";
			    
			    // Fetch previous entry for this user from 'livetable1'.
			    var fetchtimesql = "SELECT timetoconsider, livetime FROM mdl_quizaccess_hbmon_livetable1 WHERE roomid = " + socket.roomid;
			    
			    con.query(fetchtimesql, function(err, result) {
			    	if (err) throw err;
			
			    	for (i in result) {
			    		var timetoconsider = result[i].timetoconsider;
			    		var livetime = result[i].livetime;
//			    		console.log(socket.roomid + ' - Previous livetime - ' + humanise(livetime));
			    	}
			
			    	// Here, socket.timestampD is the maxdisconnecttime.
//			    	console.log(socket.roomid + ' - Current livetime is - ' + humanise(socket.timestampD - timetoconsider));
			    	
			    	// Compute cumulative livetime.
			    	livetime = parseInt(livetime) + parseInt(socket.timestampD - timetoconsider);
//			    	console.log(socket.roomid + ' - After cumulation, livetime  is - ' + humanise(livetime));
				
			    	// Update 'status' entry for this user in 'livetable1'.
			    	var updatelivetablesql = "UPDATE mdl_quizaccess_hbmon_livetable1 SET status = " + socket.currentstatus 
			    									+ ", timetoconsider = " + socket.timestampD 
			    									+ ", livetime = " + livetime 
			    									+ " where roomid = " + socket.roomid;
				    con.query(updatelivetablesql, function(err, result) {
				    	if (err) throw err;
				    });
				});
			}
		}
	}); 
});

function humanise(difference) {
    var days 	 = Math.floor(difference / (1000 * 60 * 60 * 24));
    var hours 	 = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes  = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    var mseconds = Math.floor((difference % (1000 * 60)));
    var time 	 = days + ' days, ' + hours + ' hrs, ' + minutes + ' mins, ' + mseconds + ' msecs';
    
    return time;
}

app.get('/livestatus', function(req, res) {
    var sql = "SELECT * FROM livetable1";
    con.query(sql, function(err, result, fields) {
        if (err) throw err;
        for(i in result){
        	result[i].totalconnectedsockets = totalconnectedsockets;       	
        	var roomid = "'" + result[i].roomid + "'";
        	result[i].roomwisesockets = roomwisesocketids[roomid];
        	result[i].allsockets = allsocketids;
        	result[i].allsocketscount = allsocketscount;
        }
        res.send(result);
    });
});

http.listen(port, function() {
	console.log('Listening on port ' + port);
});