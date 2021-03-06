<?php
namespace App;

use \Lib\Helper,
	\Lib\Request,
	\Lib\Response,
    \Manager\Playlist,
    \Manager\User,
    \Manager\Suggest;

class Ajax extends \Lib\Base\App {
    public function init() {
    	define('AJAX', true);
        switch (Request::get('query')) {
            case 'search':
                if ( !Request::get('offset') ) {
                    \Manager\User::create()
                        ->logHistory( Request::get('q') );
                }
                
                echo $this->render('songs');
                die;
                break;
            
            case 'suggest':
            	$suggest = new Suggest;
            	echo json_encode($suggest->get(Request::get('term', '')));
            	die;
                
            case 'addPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->addPL(
                    Request::get('name')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'delPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->delPL(Request::get('id'));

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'editPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->editPL(
                    Request::get('id'), 
                    Request::get('name')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'plStatus':
                $userManager = new User;
                $status = $userManager->updatePLSettings(
                    Request::get('id'), 
                    Request::get('status')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'moveSongToPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->moveSongToPL(
                    Request::get('fromId'), 
                    Request::get('toId'), 
                    Request::get('afterId'),
                    Request::get('songData')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'delSongFromPL':
                $playlistsManager = new Playlist;
                $status = $playlistsManager->delSongFromPL(
                    Request::get('id'), Request::get('plId')
                );

                echo json_encode(array(
                    'status' => $status
                ));
                die;
                break;
            
            case 'reloadPL':
                echo $this->render('playlists');
                die;
                break;

            case 'login':
                $request = Request::get('user');
                parse_str($request, $request);
                
                $usermanager = new User;
                $user = $usermanager->login(
                    $request['login'], $request['password']
                );
                
                echo $this->render('user');
                die;
                break;

            case 'logout':
                $usermanager = new User;
                $usermanager->logout();

                echo $this->render('user');
                die;
                break;
            
            case 'getapp':
                echo $this->render(\Lib\Request::get('getapp', 'about'));
                die;
                break;
            
            case 'dl':
            case 'getSong':
                // anti lock
                session_write_close();//@todo check
                
                $artist = urldecode(Request::get('artist'));
                $name = urldecode(Request::get('name'));
                
                # stat
                if ( $artist ) {
                    $statManager = new \Manager\Stat;
                    
                    $statManager->log(
                        $artist
                    );
                }
                # /stat
				
                $id = Request::get('id');
                
                header('Last-Modified:');
                header('ETag:');
                header('Content-Type: audio/mpeg');
                header('Accept-Ranges: bytes');
                
                # download song
				if ( 'dl' == Request::get('query') ) {
                    $fname = Helper::makeValidFname(
						$artist . ' - ' . $name
					).'.mp3';
                    
                    header("Content-Disposition: attachment; filename=\"{$fname}\"");
                    header('Content-Description: File Transfer');
                    header('Content-Transfer-Encoding: binary');
				}
                # /download song
                
                // cache
                $cachePath = CACHEROOT . "/mp3_" . $id;
                if ( \Lib\Config::getInstance()->getOption('cache', 'cacheSongs', false) && file_exists($cachePath) ) {
                    $song = file_get_contents($cachePath);
                    $contentLength = strlen($song);

                    header('Content-Length: '.$contentLength);
                    echo $song;
                    die;
                }
                // /cache
                
                $url = Request::get('url');

                $headers = get_headers($url);
                $status = substr($headers[0], 9, 3);
                
                $oldUrl = $url;
                if ( '404' == $status ) {
                    $q = $artist . ' - ' . $name;
                    
                    $searchSongs = \Lib\AudioParser::search($q);
                    
                    foreach ($searchSongs as $value) {
                        if ( $artist == $value['artist'] && $name == $value['name'] ) {
                            $url = $value['url'];
                            break;
                        }
                    }
                    
                    if ( !$url || $url = $oldUrl ) {
                        $song = reset($searchSongs);
                        $url = $song['url'];
                    }
                    
                    $playlistsManager = new Playlist;
                    $playlistsManager->updateSongInfo(
                        $id, 
                        array(
                            'url' => $url
                        )
                    );
                }
                
                
                
                $song = file_get_contents($url);
                
                if ( \Lib\Config::getInstance()->getOption('cache', 'cacheSongs', false) ) {
                    file_put_contents($cachePath, $song);
                }
                
                $contentLength = strlen($song);
                
                header('Content-Length: '.$contentLength);
                
                echo $song;
                die;
                break;

            default:
                break;
        }
    }

}
