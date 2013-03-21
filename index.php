
<?php
$genre = $_GET['genre'];
if ($genre) {
?>
<script src="http://connect.soundcloud.com/sdk.js"></script>

<script src="http://code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
        
<div id="warning-player"><big><big>Preparing player...</big></big></div>

<div id="no-songs"><big><big>There were no songs of your genre :( try to be more general</big></big></div>

<div id="warning"><big><big>Loading songs...</big></big></div>
<div id="widgetdiv">
<iframe id="sc-widget" width="100%" height="166" scrolling="no" frameborder="no" src="http://w.soundcloud.com/player/?url=http%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F1848538&show_artwork=true"></iframe>
<br>
<button style="font-size:300%" id="nextb">Next song &gt;</button> 
<button style="font-size:300%" id="playb">Play</button>
<button style="font-size:300%" id="pauseb">Pause</button>
</div>

<br><br>
All songs are licenced under creative commons and fine for commercial usage, such as playing in the restaurants or lounges. They are automatically taken from <a href="http://soundcloud.com">SoundCloud.com</a>.
<script src="https://w.soundcloud.com/player/api.js" type="text/javascript"></script>



<script>

SC.initialize({
  client_id: 'bf4a97b251e330adbb8d1590a2ea044a'
});

$('#warning-player').hide();
$('#widgetdiv').hide();
$('#no-songs').hide();

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
    currentOffset = currentOffset+1;
    var realOffset = batchsize*currentOffset;
    var resTracks;
    SC.get('/tracks', { genres: genre, license: 'cc-by', limit: batchsize , offset: realOffset ,duration: { to: 900000 }, order:'hotness' }, function(tracks) {
        resTracks=tracks;
        SC.get('/tracks', { genres: genre, license: 'cc-by-sa', limit: batchsize , offset: realOffset ,duration: { to: 900000 }, order:'hotness' }, function(tracks) {
            resTracks=resTracks.concat(tracks);
            
            SC.get('/tracks', { genres: genre, license: 'cc-by-nd', limit: batchsize , offset: realOffset ,duration: { to: 900000 }, order:'hotness' }, function(tracks) {
                resTracks=resTracks.concat(tracks);
                loadedNextBatchFinished(resTracks, callback);
            });
        });
    });   
}

function loadedNextBatchFinished(tracks, callback) {

    $('#warning').hide();

    if (tracks.length==0) {
        $('#no-songs').show();
    } else {

    
        tracks.sort(sortSongs);
        subTracks = tracks.slice(0, 50);
        allTracks = subTracks;
        console.log(tracks);
    
        callback();
    }
}

var widgetIframe = document.getElementById('sc-widget');
var widget       = SC.Widget(widgetIframe);

loadNextBatch(function() {
    
    $('#warning-player').show();
    $('#widgetdiv').show();
   
    var song = choseNextSong();
    
    widget.load(song.uri, {
       callback:function(){
          $('#warning-player').hide();
          widget.play();
          savePlaying(song);
          
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

var lastPosition=-1;
setInterval(function(){
        widget.getPosition(function(position) {
                widget.isPaused(function(paused){

                        if ((!paused) && (lastPosition==position)) {
                                playNext();
                        }
                        lastPosition=position;
                });
        });

},3000);

</script>

<?php
} else {
    ?><h1>MusicaLibre</h1>
    
    Play songs available for commercial sharing. All are licenced under creative commons and fine for commercial usage for free, such as playing in the restaurants. They are automatically taken from <a href="http://soundcloud.com">SoundCloud.com</a>. (As they are uploaded by users, they can vary in quality and genre)<br><br>
    
    The songs can start to repeat after about two hours.
    
    <form action="index.php" method="get">Genre name:
<input type="text" name="genre" value="pop">
<input type="submit" value="Search"">
</form>
<br><br>Searches to try:<br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=jazz">jazz</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=bossanova">bossanova</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=lounge">lounge</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=poprock">poprock</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=pop">pop</a><br>
<a href="http://karelbilek.com/musicalibre/index.php?genre=chillout">chillout</a><br>

<?php
}?>

<br><br>
<a href="https://github.com/pirati-cz/musicalibre">Fork on GitHub!</a>
<!---Validity? Ain't nobody got time for that!--->
