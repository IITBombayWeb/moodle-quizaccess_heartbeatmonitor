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
var obj;

// EXEC SYNC - WORKS
var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("/var/www/html/moodle/config.php"); print json_encode($CFG);\'');
obj = JSON.parse(child);

// DB CONN
var con = mysql.createConnection({
	host 	 : obj.dbhost,
	user 	 : obj.dbuser,
	password : obj.dbpass,
	database : obj.dbname
});
var dbprefix = obj.prefix;

//app.use(bodyParser.urlencoded({ extended: true }));

var totalconnectedsockets;
var roomwisesockets;
var roomwisesocketids = [];
var allsocketscount;
var allsocketids;
var cfg;
var timeserverid;
var port;
var currenttimeserverid;

//Insert new record for node server.
var nodesql = "Select * from " + dbprefix + "quizaccess_hbmon_node";
con.query(nodesql, function(err, result) {
    if (err) throw err;                
    
    if (result.length > 0) {
        for (i in result) {
            port = result[i].nodeport;
            http.listen(port, function(){});
	    break;
        }
    }
//    http.listen(3000, function(){});
});

// Insert new record for node server.
var timeserversql = "INSERT INTO " + dbprefix + "quizaccess_hbmon_timeserver (timestarted, lastlivetime) VALUES" +
							"(" + Math.floor((new Date().getTime())/1000) + "," 
								+ Math.floor((new Date().getTime())/1000) + ")";
con.query(timeserversql, function(err, result) {
	if (err) throw err;
	currenttimeserverid = result.insertId;
});
    	
// Update record for node server.
var interval = setInterval(function() {
	if (currenttimeserverid) {
		var updatetstablesql = "UPDATE " + dbprefix + "quizaccess_hbmon_timeserver SET lastlivetime = "
										+ Math.floor((new Date().getTime())/1000) 
										+ " WHERE timeserverid = " + currenttimeserverid;
		con.query(updatetstablesql, function(err, result) {
			if (err) throw err;
		});
	}
}, 5000);

function debuglog (fn, msg) {
	console.log("debug: " + new Date().toString() + " " + Math.floor((new Date().getTime()))  + " , " + "server.js | " + fn + " , " + msg);
	//console.log(Math.floor((new Date().getTime())/1000) + ": " + msg);
}

// Socket.IO
var record = io.sockets.on('connection', function (socket) {
	fn = "connect";
	debuglog(fn , socket.id + ' - ' + ' connected');
	
	allsocketscount = io.sockets.server.eio.clientsCount;
	var allclients = io.sockets.server.eio.clients;
	allsocketids = [];
	for (var clientid in allclients) {
		// This is the socket of each client in the room.
		allsocketids.push(io.sockets.server.eio.clients[clientid].id);
	}  
	
	socket.on('attempt', function(data) {
		fn = 'connect:attempt';
//		debuglog(fn , 'In attempt event.');
		
		// Append some extra data to the socket object.
        socket.roomid 			= "'" + data.roomid + "'";
        socket.socketid 		= "'" + socket.id + "'";
        socket.statusConnected 	= "'Connected'";
        socket.timestampC1 		= socket.handshake.issued;
        socket.timestampC 		= Math.floor((socket.timestampC1)/1000);
        socket.ip 				= "'" + socket.request.connection.remoteAddress + "'";

        debuglog(fn , socket.id + ' - ' + socket.roomid + ' connected');

        var sql = "INSERT INTO " + dbprefix + "quizaccess_hbmon_socketinfo1 (roomid, socketid, socketstatus, ip, timestamp) VALUES" +
         			"(" + socket.roomid + "," 
         				+ socket.socketid + "," 
         				+ socket.statusConnected + "," 
         				+ socket.ip + "," 
         				+ socket.timestampC + ")";
	    con.query(sql, function(err, result) {
	    	if (err) {
	            debuglog(fn , socket.id + ' - ' + socket.roomid + ' IP:' + socket.ip + ' record insert err \'socketinfo\'. (connect)');
	    		throw err;
            } else 
	            debuglog(fn , socket.id + ' - ' + socket.roomid + ' IP:' + socket.ip + ' record inserted in \'socketinfo\'. (connect)');
	    });

	    // Join the connected socket to the room. 'roomid' is the concatenation of (username + quizid + attemptid).
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
	    	// 'livetable' reflects current status of a user. There is one entry per user in this table. 
	    	// If exists, fetch previous entry for this user from 'livetable'.
	    	var liverecordexistsql = "SELECT * " +
	    								"FROM " + dbprefix + "quizaccess_hbmon_livetable " +
	    								"WHERE roomid = " + socket.roomid;
	    	con.query(liverecordexistsql, function(err, result) {
                if (err) throw err;                
                if (result.length > 0) {
                    for (i in result) {
                    	// Previous state details.
                        var status 			= result[i].status;
                        var room_timeserver	= result[i].timeserver;
                        var timetoconsider 	= result[i].timetoconsider;
                        var deadtime 		= result[i].deadtime;
                        var livetime 		= result[i].livetime;
                        var extratime 		= result[i].extratime;
                    }
                    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status : ' + result[i].status + '.');
                    
                	// Time server check.
                	var timeserver = [];
                	var timeserverid;
                    var timestarted;
                    var lastlivetime;
                        
                	if(currenttimeserverid != room_timeserver) {
                    	var tssql = "SELECT * FROM " + dbprefix + "quizaccess_hbmon_timeserver WHERE timeserverid IN (" + room_timeserver + ", " + currenttimeserverid + ")";
            	    	var value = con.query(tssql, function(err, tsresult) {
                            if (err) throw err;  
                            var timeserver = [];
                            if (tsresult.length > 0) {
                                for (i in tsresult) {
                                	// Previous state details.
                                    timeserver.push({
                                    	timeserverid : tsresult[i].timeserverid,
                                    	timestarted  : tsresult[i].timestarted,
                                    	lastlivetime : tsresult[i].lastlivetime
                                    })
                                }

                                // Condition 1 - Server goes down.
    	            	    	var serverdowntime;
    	            	    	var sdowntimestart = timeserver[0].lastlivetime;
    	            	    	var sdowntimeend = timeserver[1].timestarted;
    	            	    	serverdowntime = sdowntimeend - sdowntimestart;
    	            	    	
    	            	    	var userdowntime;
    	            	    	var udowntimestart = timetoconsider;
    	            	    	var udowntimeend = socket.timestampC;
    	            	    	userdowntime = udowntimeend - udowntimestart;
    	            	    	
    	            	    	// Depends on policy setup.
    	            	    	var userdowntime2;
    	            	    	userdowntime2 = udowntimeend - sdowntimestart;
    	            	    	
    	                      	// Condition 2 - Server and user, both go down.	   	    	            	    	
    	            	    	var maxdowntime;
    	            	    	maxdowntime = Math.max(serverdowntime, userdowntime, userdowntime2);
    	            	    	
    	            	    	debuglog(fn , socket.id + ' - ' + socket.roomid + ' extratime before ' + extratime);
                	    		if(maxdowntime) {
    	                        	deadtime = parseInt(deadtime) + parseInt(maxdowntime);
    	                        	extratime = parseInt(deadtime) + parseInt(extratime);
    	                        }
                	    		debuglog(fn , socket.id + ' - ' + socket.roomid + ' deadtime ' + deadtime);
                	    		debuglog(fn , socket.id + ' - ' + socket.roomid + ' extratime after ' + extratime);
                	    		
	                            var updatelivetablesql = "UPDATE " + dbprefix + "quizaccess_hbmon_livetable SET status = " 	+ socket.currentstatus 
//																+ ", deadtime = "  	+ deadtime 
																+ ", timetoconsider = " + socket.timestampC
																+ ", timeserver = " + currenttimeserverid
																+ " WHERE roomid = " + socket.roomid;
								con.query(updatelivetablesql, function(err, result) {
									if (err) {
									    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status update err \'Live\'.');
									    throw err;
									} else
									    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status updated to \'Live\'.');
								});
								/*
								if(deadtime > 60) {
									var updatelivetablesql1 = "UPDATE " + dbprefix + "quizaccess_hbmon_livetable SET " 
																	+ " deadtime = 0" 
																	+ ", extratime = " + extratime
																	+ " WHERE roomid = " + socket.roomid;
									con.query(updatelivetablesql1, function(err, result) {
										if (err) {
										    debuglog(fn , socket.id + ' - ' + socket.roomid + '\'s extratime update err.');
										    throw err;
										} else
										    debuglog(fn , socket.id + ' - ' + socket.roomid + '\'s extratime ' + extratime + ' updated.');
									}); 
                	    		}
                	    		*/
                            }
                            return timeserver;    
            	    	});  	
            		} else if (status == 'Dead' && currenttimeserverid == room_timeserver) {
		            	// Condition 3 - User goes down.                
		                // Check whether it is a ques switch or not. Ques switch time is approx. betwn. 0-2 secs.
		                // This is required when there is only one connected socket for that user.
		            	
		                // Compute cumulative deadtime.
		                deadtime = parseInt(deadtime) + parseInt(socket.timestampC - timetoconsider);  
                    	extratime = parseInt(deadtime) + parseInt(extratime);
                    	
		                var updatelivetablesql = "UPDATE " + dbprefix + "quizaccess_hbmon_livetable SET status = " 	+ socket.currentstatus 
														+ ", deadtime = " + deadtime 
														+ ", timetoconsider = " + socket.timestampC
														+ ", timeserver = " + currenttimeserverid
														+ " WHERE roomid = " + socket.roomid;
						con.query(updatelivetablesql, function(err, result) {
							if (err) {
							    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status update err \'Live\'.');
							    throw err;
							} else 
							    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status updated to \'Live\'.');
						});
						
						// Is this required? Check.
						if(deadtime > 60) {
							var updatelivetablesql1 = "UPDATE " + dbprefix + "quizaccess_hbmon_livetable SET " 
															+ " deadtime = 0" 
															+ ", extratime = " + extratime
															+ " WHERE roomid = " + socket.roomid;
							con.query(updatelivetablesql1, function(err, result) {
								if (err) {
								    debuglog(fn , socket.id + ' - ' + socket.roomid + '\'s extratime update err.');
								    throw err;
								} else
								    debuglog(fn , socket.id + ' - ' + socket.roomid + '\'s extratime ' + extratime + ' updated.');
							}); 
						}
            		} 
		    	} else {
	            	// Insert current status entry for this user in 'livetable'.                	
	                var livetablesql = "INSERT INTO " + dbprefix + "quizaccess_hbmon_livetable (roomid, status, timeserver, timetoconsider, livetime, deadtime, extratime) VALUES" +
	                                  	"(" + socket.roomid + "," 
	                                  		+ socket.currentstatus + "," 
	                                  		+ currenttimeserverid + "," 
	                                  		+ socket.timestampC + ", 0, 0 , 0)";
	                con.query(livetablesql, function(err, result) {
	                    if (err) {
	                        debuglog(fn , socket.id + ' - ' + socket.roomid + ' record insert err \'livetable\'.');
	                        throw err;
	                    } else
	                        debuglog(fn , socket.id + ' - ' + socket.roomid + ' record inserted in \'livetable\'.');
	                });
	            }
	        });	
	    }
		socket.emit('timeserver', { currenttimeserverid:currenttimeserverid });
		
		socket.on('error', (error) => {
			console.log('In \'error\' event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
		});
	});
	
	socket.on('disconnect', function() {
		fn = 'disconnect';
		debuglog(fn , socket.id + ' disconnect.');
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
            debuglog(fn , socket.id + ' - ' + socket.roomid + ' disconnected');
			// Construct record for the disconnected socket.
			socket.timestampD = Math.floor((new Date().getTime())/1000);
			socket.statusDisconnected = "'Disconnected'";
			
		    // Insert disconnection record into database.
			var sql = "INSERT INTO " + dbprefix + "quizaccess_hbmon_socketinfo1 (roomid, socketid, socketstatus, ip, timestamp) VALUES" +
					  	"(" + socket.roomid + "," 
					  		+ socket.socketid + "," 
					  		+ socket.statusDisconnected + "," 
					  		+ socket.ip + "," 
					  		+ socket.timestampD + ")";
		    con.query(sql, function(err, result) {
		    	if (err) {
			    	debuglog(fn , socket.id + ' - ' + socket.roomid + ' record insert err \'socketinfo\'. (disconnect)');
			    	throw err;	 
		    	} else
		    		debuglog(fn , socket.id + ' - ' + socket.roomid + ' record inserted in \'socketinfo\'. (disconnect)');
		    });
		    
		    // Find the total connected sockets in the room.
	        var rooms = io.sockets.adapter.rooms[socket.roomid]; 
	      
	        if (typeof rooms != 'undefined') {
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
			    
			    // Fetch previous entry for this user from 'livetable'.
			    var fetchtimesql = "SELECT timetoconsider, livetime FROM " + dbprefix + "quizaccess_hbmon_livetable WHERE roomid = " + socket.roomid;
			    
			    con.query(fetchtimesql, function(err, result) {
			    	if (err) { // TODO - Is this required?
		 				debuglog(fn , socket.id + ' - ' + socket.roomid + ' select query err discon.');			
		 				throw err;
					} else
						debuglog(fn , socket.id + ' - ' + socket.roomid + ' select query in discon.');			
				    	
					if (result.length > 0) {
						// TODO - i is always 0. Check this.
						for (i in result) {
				    		var timetoconsider = result[i].timetoconsider;
				    		var livetime = result[i].livetime;
				    	}
				
				    	// Compute cumulative livetime.
				    	livetime = parseInt(livetime) + parseInt(socket.timestampD - timetoconsider);
					
				    	// Update 'status' entry for this user in 'livetable'.
				    	var updatelivetablesql = "UPDATE " + dbprefix + "quizaccess_hbmon_livetable SET status = " + socket.currentstatus 
				    									+ ", timetoconsider = " + socket.timestampD 
				    									+ ", livetime = " + livetime 
				    									+ " where roomid = " + socket.roomid;
					    con.query(updatelivetablesql, function(err, result) {
					    	if (err) {
					    		debuglog(fn , socket.id + ' - ' + socket.roomid + ' status update err \'Dead\'.');
		                        throw err;
							} else
					    	    debuglog(fn , socket.id + ' - ' + socket.roomid + ' status updated to \'Dead\'.');
						    	//if (err) console.log(err);
					    });
					}
				});
			}
		}
	}); 
});	
