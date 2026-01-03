<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\StreamLink;

class StreamController extends Controller
{
    // --- DAFTAR SERVER "PABRIK" (COBALT INSTANCES) ---
    // Kalau satu mati, sistem otomatis pindah ke bawahnya.
    private $cobaltServers = [
        'https://cobalt.bowring.uk',      // Server Inggris (Biasanya stabil)
        'https://cobalt.kinuseka.se',     // Server Swedia
        'https://api.cobalt.tools',       // Official (Sering penuh, taruh bawah aja)
        'https://cobalt.kwiatekmiki.pl',  // Cadangan 1
        'https://w.mosfet.net',           // Cadangan 2
    ];

    public function index()
    {
        $links = StreamLink::latest()->paginate(10);
        return view('welcome', compact('links'));
    }

    // --- 1. PROSES CHECK & SAVE ---
public function check(Request $request)
{
    $request->validate(['url' => 'required|url']);
    $url = $request->input('url');
    $titleInput = $request->input('title');

    // 1. Ambil Video ID YouTube
    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
    
    if (!isset($match[1])) {
        return back()->with('error', 'Link YouTube tidak valid.');
    }
    $videoId = $match[1];

    // 2. Cek Judul (Opsional via OEmbed)
    try {
        $oembed = Http::timeout(5)->get("https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=$videoId&format=json");
        $title = $titleInput ?: ($oembed->successful() ? $oembed->json()['title'] : 'YouTube Track');
    } catch (\Exception $e) {
        $title = $titleInput ?: 'YouTube Track ' . $videoId;
    }

    // 3. PROSES KE API EXTERNAL (COBALT) SEKARANG JUGA
    $directMp3Url = $this->fetchFromCobaltList($url);

    if (!$directMp3Url) {
        return back()->with('error', 'Gagal memproses link. Semua server external sedang sibuk/down.');
    }

    // 4. SIMPAN LINK ASLI DARI API KE DATABASE
    // Kita pakai updateOrCreate supaya kalau lagunya sama, linknya terupdate yang paling baru
    StreamLink::updateOrCreate(
        ['youtube_id' => $videoId],
        [
            'url' => $directMp3Url, // Ini sekarang berisi link dari cobalt (bukan localhost)
            'title' => $title,
            'uploader_ip' => $request->ip(),
            'is_youtube' => true,
        ]
    );

    return back()->with('success', '✅ Berhasil! Link external siap dipakai.');
}

    // --- 2. SAAT DIPUTAR DI GTA (Jantung Sistem) ---
    public function streamYoutube($videoIdWithExt)
    {
        $videoId = str_replace('.mp3', '', $videoIdWithExt);
        $youtubeUrl = "https://www.youtube.com/watch?v=$videoId";

        // Cek Cache 10 menit (Biar gak spam request ke server orang)
        $audioUrl = Cache::remember("cobalt_audio_{$videoId}", 600, function () use ($youtubeUrl) {
            return $this->fetchFromCobaltList($youtubeUrl);
        });

        if ($audioUrl) {
            return redirect($audioUrl);
        }

        // Kalau gagal semua server, coba redirect ke backend backup (opsional)
        // atau return 404
        return abort(404);
    }

    // --- FUNGSI PENCARI SERVER YANG HIDUP (Round Robin) ---
    private function fetchFromCobaltList($youtubeUrl)
    {
        foreach ($this->cobaltServers as $host) {
            try {
                // Request ke API Cobalt
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Compatible; JGStream/2.0)'
                ])->timeout(4)->post("$host/api/json", [
                    'url' => $youtubeUrl,
                    'isAudioOnly' => true,
                    'aFormat' => 'mp3',
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    
                    // Cek format response (beda server kadang beda dikit)
                    if (isset($json['url'])) {
                        return $json['url']; 
                    }
                    if (isset($json['picker'][0]['url'])) {
                        return $json['picker'][0]['url'];
                    }
                }
            } catch (\Exception $e) {
                // Server ini mati? Lanjut ke server berikutnya di list
                continue; 
            }
        }
        return null; // Apes, semua server mati
    }
}
