<?php
$genre = $_GET['genre'];

$meta;
if ($genre) {
    $meta = "MusicaLibre - legally free ".htmlentities($genre)." music for public usage";
} else {
    $meta = "MusicaLibre - legally free music for public usage";
}

?>
<!DOCTYPE html>
<html>
  <head>
    <title>MusicaLibre <?php if ($genre) {echo " - ".htmlentities($genre);}?></title>
    <link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href='http://fonts.googleapis.com/css?family=Merriweather+Sans:300' rel='stylesheet' type='text/css'>
    <meta name="description" content="<?php echo $meta?>" />
    </head>
    <body>
    
    <div class="container">
    
    
    <center><h1 style="font-family: 'Merriweather Sans', sans-serif;">MusicaLibre<?php if ($genre) {echo  " - ".htmlentities($genre);}?></h1>
    

<?php

if ($genre) {
?>
<script src="http://connect.soundcloud.com/sdk.js"></script>

<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        
<div id="warning-player"><big><big>Preparing player...</big></big></div>

<div id="no-songs"><big><big>There were no songs of your genre :( try to be more general</big></big></div>
<div id="gen-error"><big><big>General error when loading songs. Try to reload.</big></big></div>

<div id="warning"><big><big>Loading songs...</big></big></div>
<div id="widgetdiv">
<iframe id="sc-widget" width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F1848538&show_artwork=true"></iframe>
<br>
<button id="nextb"  style="font-size:200%" class="btn btn-large btn-inverse">Next song &gt;</button> 
<button style="font-size:200%" class="btn btn-large btn-primary" id="playb">Play</button>
<button style="font-size:200%" class="btn btn-large btn-primary" id="pauseb">Pause</button>
</div>

<br><a href="http://karelbilek.com/musicalibre/">Back to main page...</a>

<br><br>
All songs are licenced under creative commons and fine for public usage, such as playing in the restaurants or lounges. <br><br>They are automatically taken from <a href="http://soundcloud.com">SoundCloud.com</a>. <br><br><small><i>(note: the songs are all with CC-BY, CC-BY-SA or CC-BY-ND licences.)</i></small>
<script src="https://w.soundcloud.com/player/api.js" type="text/javascript"></script>



<script>

SC.initialize({
  client_id: 'bf4a97b251e330adbb8d1590a2ea044a'
});

$('#warning-player').hide();
$('#widgetdiv').hide();
$('#no-songs').hide();
$('#gen-error').hide();

var jsonPlayed = localStorage.getItem('played');
var played;
if (jsonPlayed) {
    played = JSON.parse(jsonPlayed);
} else {
    played = new Object;
}


var genre = <?php echo json_encode($genre)?>;
var allTracks=[];

var batchsize = 100;
var currentOffset=0; //nakonec se nepouziva offset vubec na nic (puvodne jsem predpokladal postupne stahovani)
function loadNextBatch( callback) {

    var resTracks;
    realGetTracks('cc-by', function(tracks) {    
        resTracks=tracks;

        realGetTracks('cc-by-sa',  function(tracks) {
        
            resTracks=resTracks.concat(tracks);
            realGetTracks('cc-by-nd',  function(tracks) {
            
                resTracks=resTracks.concat(tracks);
                loadedNextBatchFinished(resTracks, callback);
            });
        });
    });   
}


function realGetTracks(licence, callback, recCount) {
    if (!recCount) {
        recCount = 0;
    }
    if (recCount > 30) {
        $('#warning').hide();
        $('#gen-error').show();
    } else {
    
        SC.get('/tracks', { genres: genre, license: licence, limit: batchsize , offset: 0 ,duration: { to: 900000 }, order:'hotness' }, function(resObject) {
            if (typeof resObject.errors === 'undefined') {
                callback(resObject);
            } else {
                //alert(recCount);
                setTimeout(function() {realGetTracks(licence, callback, recCount+1)},1000);
            }
        });
    }

}


function loadedNextBatchFinished(tracks, callback) {

    $('#warning').hide();

    if (tracks.length==0) {
        $('#no-songs').show();
    } else {

    
        tracks.sort(sortSongs);
        subTracks = tracks.slice(0, 50);
        allTracks = subTracks;

    
        callback();
    }
}

var widgetIframe = document.getElementById('sc-widget');
var widget       = SC.Widget(widgetIframe);

var lastPosition=-1;



loadNextBatch(function() {
    
    $('#warning-player').show();
    $('#widgetdiv').show();
   
    var song = choseNextSong();
    
    widget.load(song.uri, {
       callback:function(){
          $('#warning-player').hide();
          widget.play();
          savePlaying(song);
          
          setInterval(function(){
             widget.getPosition(function(position) {
                 widget.isPaused(function(paused){

                                                    //very rough "heuristics"
                        if ((!paused) && (lastPosition==position) && (position>=3000)) {
                                console.log(position);
                                console.log('^^^');
                                playNext();
                        }
                        lastPosition=position;
                });
              });

          },4000);

          widget.bind(SC.Widget.Events.FINISH, function() {
              playNext();
          
          
          });
       }
    });
    
    

});

function sortSongs(a, b){
        return (b.playback_count - a.playback_count) //causes an array to be sorted numerically and ascending
}

function savePlaying(song) {
    var playingSong = song;
    played[playingSong.uri] = playingSong.title;
    
    localStorage.setItem('played', JSON.stringify(played));
}

function shuffleAllTracks(){

  var i = allTracks.length, j, tempi, tempj;
  if ( i == 0 ) return false;
  while ( --i ) {
     j = Math.floor( Math.random() * ( i + 1 ) );
     tempi = allTracks[i];
     tempj = allTracks[j];
     allTracks[i] = tempj;
     allTracks[j] = tempi;
   }
}

function choseNextSong() {
    
    shuffleAllTracks();
    
    
    //for (var i=0; i<allTracks.length; i++) {
    for (var i=allTracks.length-1; i>=0; i--) {
        var song = allTracks[i];
        if (typeof played[song.uri] === 'undefined') {
            return song;
        }
        
    }
    //song still not defined :(
    played = new Object;
    localStorage.setItem('played', JSON.stringify(played));
    loadNextBatch(function() {});
    return choseNextSong();
}


function playNext() {
    $('#warning-player').show();
    var song = choseNextSong();
              widget.load(song.uri, {
                  callback:function(){
                    $('#warning-player').hide();
                    widget.play();
                    savePlaying(song);
                 }
              });
              /*z predchoziho modelu... ted je to trochu jinak
              if (playing==allTracks.length-2) {
                   $('#warning').show();
                  loadNextBatch(function(){
                        $('#warning').hide();
                  });
              }*/
}   

$('#playb').click(function() {
    widget.play();
});

$('#pauseb').click(function() {
    widget.pause();
});

$('#nextb').click(function() {
    playNext();
});



</script>

<?php
} else {
    ?>
    

    
   
    
    Play high quality songs in both personal and commercial spaces for free, legally! 
    <br><br>
    All songs are licenced under creative commons and fine for free public usage, such as playing in the restaurants or lounges. <br><br>
    They are automatically taken from <a href="http://soundcloud.com">SoundCloud.com</a>, where they are user-submitted.<br><br>
    
    (there are currently some playback issues that I am discussing with SoundCloud.com.)
    <br><hr>
    
    <form action="index.php" method="get">Genre name:
<input type="text" name="genre" value="bossanova"><br>
<input type="submit" value="Search" class="btn btn-large btn-primary">
</form>
<br><br>Searches to try:<br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=jazz">jazz</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=bossanova">bossanova</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=lounge">lounge</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=poprock">poprock</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=pop">pop</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=chillout">chillout</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=swing">swing</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=dubstep">dubstep</a> WUB WUB WUB<br>


<?php
}?>
<hr>

<a href="https://github.com/pirati-cz/musicalibre">Fork on GitHub!</a>
</center>
</div>
</body>
