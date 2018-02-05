//var config = {
//development: {
//    //url to be used in link generation
//    url: 'http://my.site.com',
//    //mongodb connection settings
//    database: {
//        host:   '127.0.0.1',
//        port:   '27017',
//        db:     'site_dev'
//    },
//    //server details
//    server: {
//        host: '127.0.0.1',
//        port: '3422'
//    }
//},
//production: {
//    //url to be used in link generation
//    url: 'http://my.site.com',
//    //mongodb connection settings
//    database: {
//        host: '127.0.0.1',
//        port: '27017',
//        db:     'site'
//    },
//    //server details
//    server: {
//        host:   '127.0.0.1',
//        port:   '3421'
//    }
//}
//};

//var port = 3000;
//var app 		= require('express')();
//var http 		= require('http').createServer(app);
//var io			= require('socket.io').listen(http);
//
//var bodyParser 	= require('body-parser');
//var mysql 		= require('mysql');
var fs 			= require('fs');
//var file1 = require('./File1')(io);
var obj;
var runner = require('child_process');

runner.exec(
'php -r \'define("CLI_SCRIPT", true); include("../../../../config.php"); print json_encode($CFG);\'', 
function (err, stdout, stderr) {
	  console.log('runner=======================================');
//	  var obj = JSON.parse(stdout).default.default;
	  obj = JSON.parse(stdout);
	 console.log(obj.dbhost);
	 console.log(obj.dbuser);
 // result botdb
});

runner.stdout.pipe(process.stdout);
runner.on('exit', function() {
  process.exit();
});


var config = {
    //url to be used in link generation
//    url: 'http://my.site.com',
    //db connection settings
    database: {
    	host 	 : obj.dbhost,
    	user 	 : obj.dbuser,
    	password : obj.dbpass,
    	database : obj.dbname
    },
    //server details
    server: {
        host: '127.0.0.1',
        port: '3422'
    }
};
console.log('config=======================================');
module.exports = config;