<?php

namespace WebSpider;

class Task
{

    protected $url = 'https://api.xinaotrip.com/openapi/server';
    protected $token = '';
    protected $taskId = '';
    protected $config = [];
    protected $cache = null;
    protected $client = null;


    /**
     * 初始化
     * @param $url  string  服务端网址
     * @param $token string 服务端密钥
     * @param $taskId string 任务编号
     */
    public function __construct($url, $token, $taskId)
    {
        $this->url = $url;
        $this->token = $token;
        $this->taskId = $taskId;
        $this->signal();
        $this->reload();
        return $this;
    }


    public function reload()
    {
        $res = $this->post('/config', ['taskId' => $this->taskId]);
        if ($res['code'] == 1) {
            $this->log($res['msg']);
            exit();
        }
        $this->config = $res['data'];
    }

    public function start()
    {
        echo 'pid=>' . getmypid() . "\n";
        $result = $this->post('/start', ['taskId' => $this->taskId, 'pid' => getmypid()]);
        if ($result['code'] == 1) {
            $this->log($result['msg']);
            exit();
        }
        $this->log($result['msg']);
    }

    public function stop()
    {
        $result = $this->post('/stop', ['taskId' => $this->taskId]);
        if ($result['code'] == 1) {
            $this->log($result['msg']);
            exit();
        }
        $this->log($result['msg']);
    }

    public function exists($key)
    {
        $result = $this->post('/exists', ['taskId' => $this->taskId, 'key' => $key]);
        if ($result['code'] == 1) {
            return false;
        }
        return true;
    }

    /**
     * 上传数据
     * @param $content
     * @return bool
     */
    public function upload($content)
    {
        $data = [];
        $data['taskId'] = $this->taskId;
        $data['content'] = $content;
        $result = $this->post('/upload', $data);
        if ($result['code'] == 1) {
            $this->log($result['msg']);
            return false;
        }
        $this->log($result['msg']);
        return true;
    }

    public function getHost()
    {
        return $this->config['website']['host'];
    }

    public function getDomain()
    {
        return $this->config['website']['domain'];
    }

    public function log($content)
    {
        echo date('Y-m-d H:i:s') . "  " . $content . "\n";
    }

    public function getCookie($refresh = false)
    {
        if ($refresh) {
            $result = $this->post('/cookie', ['taskId' => $this->taskId]);
            if ($result['code'] == 1) {
                $this->log($result['msg']);
                return '';
            } else {
                $this->log($result['msg']);
                return $result['data'];
            }
        }
        return $this->config['website']['cookie'];
    }


    protected function signal()
    {
        if (extension_loaded('pcntl') && function_exists("pcntl_signal")) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function ($signal) {
                $this->log('收到停止信号');
                $this->stop();
                exit(0);
            });
            pcntl_signal(SIGINT, function ($signal) {
                $this->log('收到手动停止信号');
                $this->stop();
                exit(0);
            });
        }
    }


    public function post($url, $data)
    {
        $url = $this->url . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 WebSpiderClient/1.0 Safari/537.36',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Authorization' => $this->token
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $html = curl_exec($ch);
        curl_close($ch);
        return json_decode($html, true);
    }
}