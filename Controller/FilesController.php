<?php

require_once('Cool/BaseController.php');
require_once('Model/FilesManager.php');

class FilesController extends BaseController
{
    public function uploadAction()
    {
        session_start();
        if(!isset($_SESSION['username']))
        {
            header('Location: ?action=login');
        }
        $data = [
            'user'    => $_SESSION,
        ];
        $manager = new FilesManager();
        if (isset($_FILES["file"]["name"]))
        {
            if (isset($_POST['name']) && $_POST['name'] !== '')
            {
                $filename = $_POST['name'];
                $filename = str_replace('/', '', $filename, $count);
                if ($count > 0)
                {
                    file_put_contents('logs/security.log', '[' . date("Y-m-d H:i:s") . '] : '. $_SESSION['username'] . ' tried to upload a file in an illegal folder' . "\n", FILE_APPEND);                    
                }
                $filename = str_replace(' ', '_', $filename);             
            } else {
                $filename = $_FILES["file"]["name"];
                $filename = str_replace('/', '', $filename, $count);
                if ($count > 0 )
                {
                    file_put_contents('logs/security.log', '[' . date("Y-m-d H:i:s") . '] : '. $_SESSION['username'] . ' tried to upload a file in an illegal folder' . "\n", FILE_APPEND);                                        
                }
                $filename = str_replace(' ', '_', $filename);    
            }
            $logs = $manager->upload($_FILES['file'], $filename);
            $data = [
                'error'   => isset($logs['error']) ? $logs['error'] : '',
                'success' => isset($logs['success']) ? $logs['success'] : '',
                'user'    => $_SESSION,
            ];
        }
        
        return $this->render('upload.html.twig', $data);
    }

    public function filesAction()
    {
        session_start();
        if(!isset($_SESSION))
        {
            header('Location: ?action=login&dir=/');
            return false;
        }
        $manager = new FilesManager();
        $folder = $_GET['dir'];
        $lowerLevel = $manager->parentFolder($folder);
        $data = $manager->scandir($_SESSION['username'].$folder);
        $folders = $manager->foldersOnly($data[0]);
        $data = [
            'error'      => isset($data[1]['error']) ? $data[1]['error'] : '',
            'directory'  => $data[0],
            'user'       => $_SESSION,
            'currentdir' => $folder,
            'lowerlevel' => $lowerLevel,
            'folders'    => $folders,
        ];
        return $this->render('files.html.twig', $data);
    }

    public function downloadAction()
    {
        $file = $_GET['file'];
        $manager = new FilesManager();
        $manager->download($file);
    }

    public function deleteAction()
    {
        $file = $_GET['file'];
        $dir = $_GET['dir'];
        $manager = new FilesManager();
        $manager->delete($file, $dir);
    }

    public function deleteDirAction()
    {
        $manager = new FilesManager();
        session_start();
        $pattern = '/(\.\..)/';
        
        $dir = "uploads/" . $_SESSION['username'] . str_replace('..', '/', $_GET['dir'], $count);
        if ($count > 0)
        {
            $lowerLevel = $manager->parentFolder($_GET['dir']);
            header("Location: ?action=files&dir=$lowerLevel");
            return file_put_contents('logs/security.log', '[' . date("Y-m-d H:i:s") . '] : '. $_SESSION['username'] . ' tried to delete a directory of a parent folder' . "\n", FILE_APPEND);            
        }
        $lowerLevel = $manager->parentFolder($_GET['dir']);
        $manager->delTree($dir);
        header("Location: ?action=files&dir=$lowerLevel");
    }

    public function moveItemAction()
    {
        $manager = new FilesManager();
        session_start();
        $basepath = "uploads/" . $_SESSION['username'];
        $from = $_GET['from'];
        $file = $_GET['file'];
        $currentDir = $manager->parentFolder($from);
        $source = $basepath . $from;
        $to = $basepath . $currentDir . '/' . $_GET['to'] . '/' . $file;
        $manager->move($source, $to);
        header('Location: ?action=files&dir=' . $manager->parentFolder($_GET['from']));
    }

    public function renameItemAction()
    {
        session_start();
        $dir = 'uploads/' . $_SESSION['username'] . $_GET['dir'] . '/';
        $from = str_replace('/', '', urldecode($_GET['from']));
        $to = str_replace('/', '', urldecode($_GET['to']), $count);
        if ($count > 0)
        {
            file_put_contents('logs/security.log', '[' . date("Y-m-d H:i:s") . '] : '. $_SESSION['username'] . ' tried to rename an item with an illegal name' . "\n", FILE_APPEND);            
            return header('Location: ?action=files&dir=' . $_GET['dir']);
        }
        $manager = new FilesManager();
        $manager->move($dir.$from, $dir.$to);
        header('Location: ?action=files&dir=' . $_GET['dir']);
    }

    public function makeDirAction()
    {
        session_start();
        $currentDir = $_GET['dir'];
        mkdir('uploads/' . $_SESSION['username'] . $_GET['dir'] . '/NewFolder');
        file_put_contents('logs/access.log', '[' . date("Y-m-d H:i:s") . '] : '. $_SESSION['username'] . ' created a directory ' . "\n", FILE_APPEND);
        header('Location: ?action=files&dir=' . $_GET['dir']);
    }

    public function viewAction()
    {
        session_start();
        $manager = new FilesManager();
        $file = 'uploads/' . $_SESSION['username'] . $_GET['file'];
        $type = $_GET['type'];
        if ($type == 'txt')
        {
            $textfileContent = $manager->readFile($file);
        }
        $data = [
            'user'        => $_SESSION,
            'filepath'        => $file,
            'filetype'        => $type,
            'textfileContent' => isset($textfileContent) ? $textfileContent : '',
        ];
        return $this->render('viewFile.html.twig', $data);
    }

    public function editFileAction()
    {
        session_start();
        $manager = new FilesManager();
        $file = 'uploads/' . $_SESSION['username'] . $_GET['file'];
        if (isset($_GET['file-content']))
        {
            $manager->editFile($file, $_GET['file-content']);
            header("Location: ?action=view&file=" . $_GET['file'] . "&type=" . $_GET['type']);
        } else {
            $type = $_GET['type'];
            if ($type == 'txt')
            {
                $textfileContent = $manager->readFile($file);
            }
            $data = [
                'user'            => $_SESSION,
                'file'            => $_GET['file'],
                'filepath'        => $file,
                'filetype'        => $type,
                'textfilecontent' => isset($textfileContent) ? $textfileContent : '',
            ];
            return $this->render('editFile.html.twig', $data);
        }
    }
}