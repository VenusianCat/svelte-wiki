<script>
	import marked from "marked";
	import { config } from "./stores";

	marked.setOptions({
		gfm: true,
		breaks: true,
		sanitize: false,
		smartLists: true,
		smartypants: false,
		xhtml: false,
	});

	let hash = getHash();
	let TopNav = "placeholder";
	console.log(hash);
	let wikiMetaData = { wikiTags: Array() };

	$: pageTitle = reSpace(hash);
	$: taggedPages = wikiMetaData.wikiTags[hash.toLowerCase()];
	console.log(taggedPages);

	let value = `Please wait while content loads...`;

	/* my even newer more generic function to grab text from the API */
	const getResponseFromAPI = async (action, id, responseType) => {
		const response = await fetch($config.ajaxURL, {
			method: "post",
			headers: {
				Accept: "application/json, text/plain, /",
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ action: action, id: id }),
		});
		if (responseType == "json") {
			const processedResponse = await response.json();
			return processedResponse;
		} else {
			const processedResponse = await response.text();
			return processedResponse;
		}
	};

	getResponseFromAPI("getWikiMetadata", null, "json").then(function (result) {
		wikiMetaData = result;
		console.log(wikiMetaData);
	});

	/* grab the topnav */
	getResponseFromAPI("load", "TopNav", "text").then(function (result) {
		TopNav = result;
	});

	/* grab the main page body */
	getResponseFromAPI("load", hash, "text").then(function (result) {
		value = result;
	});

	function getHash() {
		let fullHash = new URL(document.URL).hash;
		let processedHash = "Home";
		if (fullHash !== "#" && fullHash !== "") {
			processedHash = fullHash.slice(1);
		}
		console.log(processedHash);
		return processedHash;
	}

	/* beyond Markdown syntax extensions;  */
	/* concept referred to as "Gimmicks" in MDwiki (see http://dynalon.github.io/mdwiki/#!gimmicks.md) */
	/* FWIW I attempted to implement this "right" way be extending the marked.js parser (https://marked.js.org/using_pro#extensions )  */
	/* but could not get it to work correctly */

	function postParse(text) {
		/* Consider strings surrounded by double square brackets as "wikiwords" - automatic hyperlinking to pages of that name */

		const wikiWordFormat = /\[\[([A-Za-z ]+)\]\]/g;

		let wikiText = text.replaceAll(wikiWordFormat, function (raw, match) {
			let displayClass = "wikiWord";
			if (
				typeof wikiMetaData.activeWikiWords !== "undefined" &&
				wikiMetaData.activeWikiWords.indexOf(match) === -1
			) {
				displayClass = "newWikiWord";
			}
			return (
				'<a class="' +
				displayClass +
				'" href="#' +
				deSpace(match) +
				'">' +
				match +
				"</a>"
			);
		});

		/* replace strings of format n/m with an HTML symbol for the vulgar fraction */
		const VulgarFraction = /\b([0-9])\/([0-9])\b/g;
		wikiText = wikiText.replaceAll(
			VulgarFraction,
			function (match, numerator, denominator) {
				console.log(match, numerator, denominator);
				return "&frac" + numerator + denominator + ";";
			}
		);

		/* find and format display tags of the format {tag} or {tag|background color} */
		const tag = /{([^|}]+)(|[^}]+)?}/g;
		wikiText = wikiText.replaceAll(tag, function (raw, tagText, colorText) {
			console.log(raw, tagText, colorText);
			let colorHTML = "";
			if (colorText) {
				colorHTML =
					' style="background-color:' + colorText.substr(1) + '"';
			}
			return '<span class="tag"' + colorHTML + ">" + tagText + "</span>";
		});

		return wikiText;
	}

	function reSpace(text) {
		return text.replaceAll(/([a-z])([A-Z])/g, "$1 $2");
	}

	function deSpace(text) {
		return text.replaceAll(/ /g, "");
	}

	window.addEventListener("hashchange", function () {
		hash = getHash();
		getResponseFromAPI("load", hash, "text").then(function (result) {
			value = result;
		});
	});

	function saveData() {
		const response = fetch($config.ajaxURL, {
			method: "post",
			headers: {
				Accept: "application/json, text/plain, /",
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ action: "save", id: hash, content: value }),
		})
			.then((response) => response.text())
			.then((text) => {
				console.log(text);
			});
	}
</script>

<section id="container">
	<section id="pageContent">
		<nav>{@html postParse(marked(TopNav))}</nav>
		<p>Now viewing: {pageTitle}</p>
		{#if hash !== "Index"}
			{@html postParse(marked(value))}
		{:else}
			<ul>
				{#if typeof wikiMetaData.allWikiWords !== "undefined"}
					{#each wikiMetaData.allWikiWords as page}
						<li>{@html postParse("[[" + page + "]]")}</li>
					{/each}
				{/if}
			</ul>
		{/if}

		{#if typeof taggedPages !== "undefined" && taggedPages.length > 0}
			<h3>Pages tagged "{hash}"</h3>
			<nav>
				<ul>
					{#each taggedPages as taggedPage}
						<li>{@html postParse("[[" + taggedPage + "]]")}</li>
					{/each}
				</ul>
			</nav>
		{/if}
	</section>
	{#if hash !== "Index"}
		<section id="editor">
			<h2>
				Markdown Editor
				<button id="saveData" on:click={saveData}>Save</button>
			</h2>
			<textarea bind:value />
		</section>
	{/if}
</section>

<style>
	#editor {
		margin-top: 30px;
		padding-top: 30px;
	}
	textarea {
		width: 100%;
		height: 400px;
		padding: 20px;
		margin-bottom: 100px;
		background-color: #e0e0e0;
		color: hsl(0, 0%, 10%);
		line-height: 2em;
	}
	#saveData {
		display: inline-block;
		margin-left: 80px;
		font-size: 0.8em;
		padding: 2px 10px;
	}

	@media (min-width: 800px) {
		#container {
			display: flex;
			gap: 80px;
		}
		#editor,
		#pageContent {
			flex-basis: 50%;
			margin-top: 0;
			padding-top: 0;
		}
		textarea {
			height: 100%;
		}
	}
</style>
