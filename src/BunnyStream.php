<?php

namespace Bunny\Stream;

use Bunny\Stream\Exception\BunnyStreamAuthenticationException;
use Bunny\Stream\Exception\BunnyStreamFileNotFoundException;
use Bunny\Stream\Exception\BunnyStreamException;

class BunnyStream
{

    public $apiAccessKey = '';
    public $library_id = '';


    public function __construct($apiAccessKey, $library_id)
    {
        $this->library_id = $library_id;
        $this->apiAccessKey = $apiAccessKey;
    }


    /**
     * @param int $type
     * @return string
     */
    private function getBaseUrl(int $type = 1)
    {
        return $type == 1 ? "https://video.bunnycdn.com/library/{$this->library_id}/videos/" : "https://video.bunnycdn.com/library/{$this->library_id}/collections/";
    }

    /**
     * @param string $collection
     * @param string $search
     * @param int    $orderby
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function List($collection = null, $search = null, int $orderby = null, int $items = 100, int $page = 1)
    {
        $dt1 = ['page' => $page];
        $dt2 = ['itemsPerPage' => $items];
        $prop1 = ['collection' => $collection];
        $prop2 = ['search' => $search];
        if (isset($orderby)) {
            $orderby_array = [1, 2];
            if (in_array($orderby, $orderby_array)) {
                $value = ($orderby == 1) ? 'date' : 'title';
                $prop3 = ['orderBy' => $value];
            } else {
                throw new BunnyStreamException("OrderBy can only be 1 or 2");
            }
        } else {
            $prop3 = array();
        }
        $errg = array_merge($dt1, $dt2, $prop1, $prop2, $prop3);
        $prop_array = array_filter($errg);
        $request_query = http_build_query($prop_array);
        $query = "?" . $request_query;
        return $this->APIcall($query);
    }

    /**
     * @param string $search
     * @param int $orderby
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function ListCollections($search = null, int $orderby = null, int $items = 100, int $page = 1)
    {
        $dt1 = ['page' => $page];
        $dt2 = ['itemsPerPage' => $items];
        $prop2 = ['search' => $search];
        if (isset($orderby)) {
            $orderby_array = [1, 2];
            if (in_array($orderby, $orderby_array)) {
                $value = ($orderby == 1) ? 'date' : 'title';
                $prop3 = ['orderBy' => $value];
            } else {
                throw new BunnyStreamException("OrderBy can only be 1 or 2");
            }
        } else {
            $prop3 = array();
        }
        $errg = array_merge($dt1, $dt2, $prop2, $prop3);
        $prop_array = array_filter($errg);
        $request_query = http_build_query($prop_array);
        $query = "?" . $request_query;
        return $this->APIcall($query, "GET", null, null, null, 2);
    }


    /**
     * @param string $videoId
     * @throws BunnyStreamException
     * @return string|object
     */
    public function get($videoId)
    {
        if (!isset($videoId)) {
            throw new BunnyStreamException("Missing required arguments");
        }
        $data = $this->APIcall($videoId);
        return $this->unmarshal($data);
    }


    /**
     * @param string $collectionId
     * @throws BunnyStreamException
     * @return string
     */
    public function getCollection($collectionId)
    {
        if (!isset($collectionId)) {
            throw new BunnyStreamException("Missing required arguments");
        }
        $data = $this->APIcall($collectionId, null, null, null, null, 2);
        return $this->unmarshal($data);
    }


    /**
     * @param string $videoId
     * @param string $title
     * @param string $collectionId
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function update($videoId, $title, $collectionId = null)
    {
        if (!isset($videoId, $title)) {
            throw new BunnyStreamException("Missing required arguments");
        }

        if (isset($collectionId)) {
            $collection = array('collectionId' => $collectionId);
        } else {
            $collection = array();
        }

        $update_data = json_encode(array_merge(array('title' => $title), $collection));
        return $this->APIcall($videoId, "POST", null, null, $update_data);
    }


    /**
     * @param string $CollectionID
     * @param string $name
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function updateCollection($collectionId, $name)
    {
        if (!isset($collectionId, $name)) {
            throw new BunnyStreamException("Missing required arguments");
        }
        $update_data = json_encode(array('name' => $name));
        return $this->APIcall($collectionId, "POST", null, null, $update_data, 2);
    }


    /**
     * @param string $title
     * @param string $collectionId
     * @throws BunnyStreamException
     * @return bool|object
     */
    public function create($title, $collectionId = null)
    {
        if (!isset($title)) {
            throw new BunnyStreamException("Missing required arguments");
        }
        if (isset($collectionId)) {
            $collection = array('collectionId' => $collectionId);
        } else {
            $collection = array();
        }
        $data = json_encode(array_merge($collection, array('title' => $title)));
        $video_data = $this->APIcall(null, "POST", null, null, $data);
        return $this->unmarshal($video_data);
    }


    /**
     * @param string $title
     * @return bool|string
     */
    public function createCollection($name)
    {
        if (!isset($name)) {
            throw new BunnyStreamException("Missing name");
        }
        $data = json_encode(array('name' => $name));
        $collection_data = $this->APIcall(null, "POST", null, null, $data, 2);
        return $this->unmarshal($collection_data)->guid;
    }

    /**
     * @param string $source
     * @param string $title
     * @param string $collectionId
     * @param string $videoId
     * @throws BunnyStreamException
     * @return string
     */
    public function upload($source, $title, $collectionId = null, $videoId = null)
    {

        if (!is_readable($source)) {
            throw new BunnyStreamException("The source file must be readable.");
        }

        if ($videoId === null) {
            if (!isset($title)) {
                $title = basename($source);
            }

            $videoId = $this->create($title, $collectionId)->guid;
        }

        // Open the local file
        $fileStream = fopen($source, "r");
        if ($fileStream == false) {
            throw new BunnyStreamException("The local file could not be opened.");
        }
        $dataLength = filesize($source);
        $this->APIcall($videoId, "PUT", $fileStream, $dataLength);
        return $videoId;
    }

    /**
     * @param string $videoId
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function delete($videoId)
    {
        if (!isset($videoId)) {
            throw new BunnyStreamException("Missing ID");
        }
        return $this->APIcall($videoId, "DELETE");
    }

    /**
     * @param string $collectionId
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function deleteCollection($collectionId)
    {
        if (!isset($collectionId)) {
            throw new BunnyStreamException("Missing ID");
        }
        return $this->APIcall($collectionId, "DELETE", null, null, null, 2);
    }


    /**
     * @param string $videoId
     * @param string $url image URL to upload
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function uploadThumbnail($videoId, $url)
    {

        if (!isset($videoId, $url)) {
            throw new BunnyStreamException("Missing required arguments.");
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            throw new BunnyStreamException('Not a valid URL');
        }

        $composition = $videoId . "/thumbnail?thumbnailUrl=" . $url;

        return $this->APIcall($composition, "POST");
    }

    /**
     * @param string $source
     * @param string $videoId
     * @param string $srclang
     * @param string $label
     * @throws BunnyStreamException
     * @return bool|string
     */

    public function addCaption($source, $videoId, $srclang, $label)
    {
        if (!isset($source, $videoId, $srclang, $label)) {
            throw new BunnyStreamException("Missing required arguments.");
        }
        if (!is_readable($source)) {
            throw new BunnyStreamException("The source file must be readable.");
        }
        $source_content = file_get_contents($source);
        $file = base64_encode($source_content);
        $data = json_encode(array('srclang' => $srclang, 'label' => $label, 'captionsFile' => $file));
        $composition = $videoId . '/captions/' . $srclang;

        return $this->APIcall($composition, "POST", null, null, $data);
    }

    /**
     * @param string $videoId
     * @param string $srclang
     * @param string $label
     * @throws BunnyStreamException
     * @return bool|string
     */

    public function deleteCaption($videoId, $srclang)
    {
        if (!isset($videoId, $srclang)) {
            throw new BunnyStreamException("Missing required arguments.");
        }
        $composition = $videoId . '/captions/' . $srclang;

        return $this->APIcall($composition, "DELETE");
    }


    /**
     * @param string $videoId
     * @param string $url
     * @param array  $headers
     * @throws BunnyStreamException
     * @return bool|string
     */
    public function fetch($url, $videoId = null, array $headers = null)
    {
        if (!isset($url)) {
            throw new BunnyStreamException("Missing required arguments.");
        }
        if ($videoId === null) {
            $title = basename($url);
            $videoId = $this->create($title)->guid;
        }

        if (isset($headers)) {
            $header = ['headers' => array($headers)];
        }

        $data = (isset($header))
        ? json_encode(array_merge(array('url' => $url), $header))
        : json_encode(array('url' => $url));

        return $this->APIcall($videoId . '/fetch', 'POST', null, null, $data);
    }


    /**
     * Sends a HTTP Request using cURL
     * 
     * @param string $url
     * @param string $method
     * @param resource $uploadFile
     * @param int $uploadFileSize
     * @param string $properties
     * @param int $type
     * @return bool|string
     * @throws BunnyStreamException
     * @throws BunnyStreamFileNotFoundException
     * 
     */
    private function APIcall($url = null, $method = "GET", $uploadFile = null, $uploadFileSize = null, $properties = null, $type = 1)
    {
        $ch = curl_init();
        if (isset($url)) {
            curl_setopt($ch, CURLOPT_URL, $this->getBaseUrl($type) . $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->getBaseUrl($type));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);

        if ($method == "PUT" && isset($uploadFile)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_UPLOAD, 1);
            curl_setopt($ch, CURLOPT_INFILE, $uploadFile);
            curl_setopt($ch, CURLOPT_INFILESIZE, $uploadFileSize);
        } else if ($method == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        } else if ($method == "POST") {
            curl_setopt($ch, CURLOPT_POST, TRUE);
        }

        if ($method == "POST" && isset($properties)) {
            $fields = $properties;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }

        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "AccessKey: {$this->apiAccessKey}",
                "Content-Type: application/json",
            ));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "AccessKey: {$this->apiAccessKey}"
            ));
        }

        $output = curl_exec($ch);
        $curlError = curl_errno($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            throw new BunnyStreamException("An unknown error has occured during the request. Status code: " . $curlError);
        }
        if ($responseCode == 404) {
            throw new BunnyStreamFileNotFoundException($url);
        } else if ($responseCode == 401) {
            throw new BunnyStreamAuthenticationException($this->library_id, $this->apiAccessKey);
        } else if ($responseCode < 200 || $responseCode > 299) {
            throw new BunnyStreamException("An unknown error has occured during the request. Status code: " . $responseCode);
        }

        return $output;
    }

    private function unmarshal($message)
    {
        return json_decode($message);
    }
}
