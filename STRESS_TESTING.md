
# 压力测试

机器信息:

CPU相关:

```
[root@MyCloudServer ~]# lscpu
Architecture:          x86_64
CPU op-mode(s):        32-bit, 64-bit
Byte Order:            Little Endian
CPU(s):                2
On-line CPU(s) list:   0,1
Thread(s) per core:    1
Core(s) per socket:    1
Socket(s):             2
NUMA node(s):          1
Vendor ID:             GenuineIntel
CPU family:            6
Model:                 63
Stepping:              2
CPU MHz:               2400.070
BogoMIPS:              4800.14
Hypervisor vendor:     Xen
Virtualization type:   full
L1d cache:             32K
L1i cache:             32K
L2 cache:              256K
L3 cache:              15360K
NUMA node0 CPU(s):     0,1
```


```
[root@MyCloudServer ~]# cat /proc/cpuinfo
processor       : 0
vendor_id       : GenuineIntel
cpu family      : 6
model           : 63
model name      : Intel(R) Xeon(R) CPU E5-2620 v3 @ 2.40GHz
stepping        : 2
microcode       : 54
cpu MHz         : 2400.070
cache size      : 15360 KB
physical id     : 17
siblings        : 1
core id         : 0
cpu cores       : 1
apicid          : 17
initial apicid  : 17
fpu             : yes
fpu_exception   : yes
cpuid level     : 15
wp              : yes
flags           : fpu de tsc msr pae cx8 cmov pat clflush mmx fxsr sse sse2 ht syscall lm constant_tsc rep_good unfair_spinlock pni pclmulqdq ssse3 fma cx16 sse4_1 sse4_2 movbe popcnt aes xsave avx f16c rdrand hypervisor lahf_lm abm ida arat epb xsaveopt pln pts dts fsgsbase bmi1 avx2 bmi2 erms
bogomips        : 4800.14
clflush size    : 64
cache_alignment : 64
address sizes   : 46 bits physical, 48 bits virtual
power management:
processor       : 1
vendor_id       : GenuineIntel
cpu family      : 6
model           : 63
model name      : Intel(R) Xeon(R) CPU E5-2620 v3 @ 2.40GHz
stepping        : 2
microcode       : 54
cpu MHz         : 2400.070
cache size      : 15360 KB
physical id     : 17
siblings        : 1
core id         : 0
cpu cores       : 1
apicid          : 17
initial apicid  : 17
fpu             : yes
fpu_exception   : yes
cpuid level     : 15
wp              : yes
flags           : fpu de tsc msr pae cx8 cmov pat clflush mmx fxsr sse sse2 ht syscall lm constant_tsc rep_good unfair_spinlock pni pclmulqdq ssse3 fma cx16 sse4_1 sse4_2 movbe popcnt aes xsave avx f16c rdrand hypervisor lahf_lm abm ida arat epb xsaveopt pln pts dts fsgsbase bmi1 avx2 bmi2 erms
bogomips        : 4800.14
clflush size    : 64
cache_alignment : 64
address sizes   : 46 bits physical, 48 bits virtual
power management:
```

测试硬盘速度:

```
[root@MyCloudServer ~]# hdparm -Tt /dev/xvda
/dev/xvda:
 Timing cached reads:   18706 MB in  1.98 seconds = 9423.90 MB/sec
 Timing buffered disk reads: 1178 MB in  3.00 seconds = 392.62 MB/sec
```

压测接口统一输出 `{"time":1478859517,"str":"hello"}`, 其中time是当前时间戳.

压测工具使用 wrk, 因为实际测试 wrk 比 ab 要准确一点点.

## php5.6 fpm + 原生php

php 开启了 opcache.

主要的 fpm 配置:

```
[www]
listen = /tmp/php-cgi.sock
listen.backlog = -1
listen.allowed_clients = 127.0.0.1
listen.owner = www
listen.group = www
listen.mode = 0666
user = www
group = www
pm = dynamic
pm.max_children = 20
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 20
request_terminate_timeout = 100
request_slowlog_timeout = 0
slowlog = var/log/slow.log
```

新建文件 `josn.php`:

```
<?php
echo json_encode(['time' => time(), 'str' => 'hello']);
```

先尝试 10000 个连接

```
[root@MyCloudServer default]# ~/wrk/wrk -c10000 -d10s -t4 --timeout=10 http://127.0.0.1/json.php
Running 10s test @ http://127.0.0.1/json.php
  4 threads and 10000 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   233.05ms    1.04s    9.12s    95.57%
    Req/Sec     4.87k     3.34k   12.10k    54.55%
  177384 requests in 10.06s, 53.45MB read
  Non-2xx or 3xx responses: 177370
Requests/sec:  17633.04
Transfer/sec:      5.31MB
```

异常请求有 17 万多, 说明并发数太大了, php-fpm 顶不住了, 测试后发现的确是返回了 `502 Bad Gateway`. 逐步降低 c 参数, 在降低到 150 时, 稳定可以跑得结果:

```
[root@MyCloudServer default]# ~/wrk/wrk -c150 -d10s -t4 --timeout=10 http://127.0.0.1/json.php
Running 10s test @ http://127.0.0.1/json.php
  4 threads and 150 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency    32.65ms    7.09ms  56.02ms   68.39%
    Req/Sec     1.14k   249.05     2.17k    74.25%
  45283 requests in 10.01s, 11.05MB read
Requests/sec:   4523.17
Transfer/sec:      1.10MB
```

## php5.6 fpm + yii2

直接使用官方的例子来做测试, `wget https://github.com/yiisoft/yii2/releases/download/2.0.10/yii-advanced-app-2.0.10.tgz`, 创建 `frontend/controllers/ApiController.php`:

```
<?php
namespace frontend\controllers;
use demo\models\User;
use Yii;
use yii\web\Controller;
use yii\web\Response;
/**
 * API相关的测试控制器
 *
 * @package demo\controllers
 */
class ApiController extends Controller
{
    /**
     * 返回json
     *
     * @return array
     */
    public function actionJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['time' => time(), 'str' => 'hello'];
    }
}
```
增加 nginx 配置后开始测试.

跟上面的 php-fpm 压测情况一样, c 参数太大了, 需要调整才能跑完成所有请求:

```
[root@MyCloudServer yii2-app-advanced]# ~/wrk/wrk -c140 -d10s -t4 --timeout=10 "http://yii2-app-advanced/index.php?r=api/json"
Running 10s test @ http://yii2-app-advanced/index.php?r=api/json
  4 threads and 140 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   515.08ms   69.55ms 628.30ms   92.97%
    Req/Sec    83.93     81.08   281.00     79.77%
  2645 requests in 10.07s, 619.92KB read
Requests/sec:    262.75
Transfer/sec:     61.58KB
```

最终调整到 140 才能保证没有 Non-2XX 请求.

## php5.6 + swoole 裸跑

创建脚本 `swoole_http_test.php`:

```
<?php
$http = new swoole_http_server("127.0.0.1", 9501);
$http->on('request', function ($request, $response) {
    $response->end(json_encode(['time' => time(), 'str' => 'hello']));
});
$http->start();
```

执行 `php swoole_http_test.php`, 然后开始压测, 得到结果:

```
[root@MyCloudServer swoole]# ~/wrk/wrk -c10000 -d10s -t4 --timeout=10 http://127.0.0.1:9501
Running 10s test @ http://127.0.0.1:9501
  4 threads and 10000 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency   391.09ms   53.00ms 506.82ms   80.97%
    Req/Sec     6.22k     2.28k   13.20k    67.63%
  239516 requests in 10.06s, 42.49MB read
Requests/sec:  23808.53
Transfer/sec:      4.22MB
```

## php5.6 + swoole-yii2

demo 测试脚本中的 worker 数太少, 将其改成 CPU 核数的两倍.

```
    ...
    'server' => [
        'worker_num' => 4,
        'max_request' => 20000,
        // 任务进程数
        'task_worker_num' => 4,
    ],
    ...
```

执行 `php demo/index.php`

得到结果:

```
[root@MyCloudServer swoole-yii2]# ~/wrk/wrk -c10000 -d10s -t4 --timeout=10 http://127.0.0.1:6677/api/json
Running 10s test @ http://127.0.0.1:6677/api/json
  4 threads and 10000 connections
  Thread Stats   Avg      Stdev     Max   +/- Stdev
    Latency     2.04s   537.74ms   2.42s    85.10%
    Req/Sec     1.19k   724.98     3.64k    58.77%
  41248 requests in 10.03s, 8.18MB read
Requests/sec:   4110.70
Transfer/sec:    834.99KB
```

## 结果

按照 QPS 排名, 上面的几种方式, 排列为:

* php5.6 + swoole 裸跑: 23808
* php5.6 fpm + 原生php: 4523
* php5.6 + swoole-yii2: 4110
* php5.6 fpm + yii2: 262

其中 swoole 裸跑的性能最佳, 如果你的接口有高并发需求, 并且业务比较简单(如简单的登录接口), 强烈建议使用 swoole 来实现.

php-fpm 和本项目的性能接近, 也就是使用本项目的产品, 在业务层的实现, 性能已经更裸写 php 十分接近, 如果业务复杂的话, 甚至可以比原生 php 还要快.

php-fpm搭载 yii2, 这种方式效率最低, 性能跟上面对比差了 10 - 100 倍.
不过 php-fpm 有其自有的优势, 一次使用即可回收的机制, 可以让 PHPER 写起代码来可以理直气壮地偷懒. :)

如果对上面的压测结果有看法, 可以提 issue 我们一起讨论一下~
