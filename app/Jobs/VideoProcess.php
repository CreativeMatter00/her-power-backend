<?php

namespace App\Jobs;

use App\Models\CourseVideo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VideoProcess implements ShouldQueue
{
    use Queueable;
    protected $filePath;
    protected $fileName;
    protected $course_id;
    protected $directory;
    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $fileName, $course_id, $directory)
    {
        $this->filePath = $filePath;
        $this->fileName = $fileName;
        $this->course_id = $course_id;
        $this->directory = $directory;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $courseId = (int) $this->course_id;
        CourseVideo::where('session_id', $courseId)->update(['video_code' => 'Processing']);
        // Define output directory
        $outputDir = public_path(dirname($this->filePath) . '/' . $this->course_id . '/');

        // Ensure output directory exists
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        // Define resolutions for adaptive streaming
        $resolutions = [
            // '240p' => ['scale' => '426:240', 'bitrate' => '800k', 'maxrate' => '856k', 'bufsize' => '1200k'],
            // '360p' => ['scale' => '640:360', 'bitrate' => '1400k', 'maxrate' => '1498k', 'bufsize' => '2100k'],
            '480p' => ['scale' => '854:480', 'bitrate' => '2800k', 'maxrate' => '2996k', 'bufsize' => '4200k'],
            '720p' => ['scale' => '1280:720', 'bitrate' => '5000k', 'maxrate' => '5350k', 'bufsize' => '7500k']
            // '1080p' => ['scale' => '1920:1080', 'bitrate' => '8000k', 'maxrate' => '8560k', 'bufsize' => '12000k']
        ];
        foreach ($resolutions as $key => $res) {
            $command = sprintf(
                'ffmpeg -i %s -vf scale=%s -c:a aac -ar 48000 -c:v h264 -profile:v main -crf 20 -sc_threshold 0 -g 48 -keyint_min 48 -hls_time 4 -hls_playlist_type vod -b:v %s -maxrate %s -bufsize %s -hls_segment_filename "%s%s_%%03d.ts" "%s%s.m3u8"',
                escapeshellarg(public_path($this->filePath)),
                $res['scale'],
                $res['bitrate'],
                $res['maxrate'],
                $res['bufsize'],
                $outputDir,
                $key,
                $outputDir,
                $key
            );
            shell_exec($command . ' 2>&1');
        }

        // Generate master playlist for adaptive streaming
        $masterPlaylist = "#EXTM3U\n";
        foreach ($resolutions as $key => $res) {
            $masterPlaylist .= sprintf(
                "#EXT-X-STREAM-INF:BANDWIDTH=%s,RESOLUTION=%s\n%s.m3u8\n",
                str_replace('k', '000', $res['bitrate']),
                $res['scale'],
                $key
            );
        }
        file_put_contents($outputDir . $this->fileName . '.m3u8', $masterPlaylist);
        $dbDir = $this->directory . $this->course_id . '/' . $this->fileName . '.m3u8';
        CourseVideo::where('session_id', $courseId)->update(['video_link' => $dbDir, 'video_code' => 'Completed']);

        //  Delete main file after video process is done.
        $deleteFile = public_path($this->filePath);
        if (file_exists($deleteFile)) {
            unlink($deleteFile);
        }
    }
}
