<?php
/**
 * Gem - Song Embed (songlist field type)
 *
 * Confirmed provider list: Spotify, YouTube, Apple Music, SoundCloud.
 * Reject at input if a URL doesn't match any of these - never store an
 * un-embeddable entry.
 *
 * Storage shape: field value is a JSON array of {url, provider} objects.
 * embed_url is deliberately NOT stored - it's derived at render time from
 * url+provider, so if an embed URL pattern ever needs to change, existing
 * data doesn't go stale.
 */

/**
 * Detects the provider from a URL and returns {provider, embed_url}, or
 * null if it doesn't match any known provider.
 */
function gem_detect_song_provider($url)
{
	if (preg_match('#^https?://open\.spotify\.com/track/([A-Za-z0-9]+)#', $url, $m))
	{
		return array(
			'provider'  => 'spotify',
			'embed_url' => 'https://open.spotify.com/embed/track/' . $m[1],
		);
	}

	if (preg_match('#^https?://(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]+)#', $url, $m)
		|| preg_match('#^https?://youtu\.be/([A-Za-z0-9_-]+)#', $url, $m))
	{
		return array(
			'provider'  => 'youtube',
			'embed_url' => 'https://www.youtube.com/embed/' . $m[1],
		);
	}

	if (preg_match('#^https?://music\.apple\.com/(.+)$#', $url, $m))
	{
		// Apple's embed pattern is the same URL, just on the embed subdomain.
		return array(
			'provider'  => 'apple_music',
			'embed_url' => 'https://embed.music.apple.com/' . $m[1],
		);
	}

	if (preg_match('#^https?://(?:www\.)?soundcloud\.com/[A-Za-z0-9_\-/]+#', $url))
	{
		return array(
			'provider'  => 'soundcloud',
			'embed_url' => 'https://w.soundcloud.com/player/?url=' . urlencode($url) . '&amp;auto_play=false',
		);
	}

	return null;
}

/**
 * Renders one <iframe> for a single {url, provider} entry. Dimensions are
 * deliberately modest defaults - restyle freely, this is functional not
 * final.
 */
function gem_render_song_embed($entry)
{
	$detected = gem_detect_song_provider($entry['url']);
	if (!$detected)
	{
		return ''; // shouldn't happen if save-side validation worked, but never render a broken embed
	}

	$height = ($detected['provider'] === 'youtube') ? 200 : 80;

	return '<iframe src="' . $detected['embed_url'] . '" width="100%" height="' . $height . '" frameborder="0" allow="encrypted-media" loading="lazy"></iframe>';
}

/**
 * Renders every entry in a songlist field's JSON-decoded value as a
 * stacked set of embeds.
 */
function gem_render_songlist($json_value)
{
	$entries = json_decode($json_value, true);
	if (!is_array($entries))
	{
		return '';
	}

	$html = '<div class="gem-songlist">';
	foreach ($entries as $entry)
	{
		if (!isset($entry['url']))
		{
			continue;
		}
		$html .= gem_render_song_embed($entry);
	}
	$html .= '</div>';

	return $html;
}
