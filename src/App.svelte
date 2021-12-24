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
	let Footer = "placeholder";
	console.log(hash);
	let pageIndex = Array();

	$: pageTitle = reSpace(hash);

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

	getResponseFromAPI("getIndex", null, "json").then(function (result) {
		pageIndex = result;
		console.log(pageIndex);
	});

	/* grab the topnav */
	getResponseFromAPI("load", "TopNav", "text").then(function (result) {
		TopNav = result;
	});

	/* grab the footer */
	getResponseFromAPI("load", "Footer", "text").then(function (result) {
		Footer = result;
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

	/* beyond Markdown syntax extensions; 
	concept referred to as "Gimmicks" in MDwiki (see http://dynalon.github.io/mdwiki/#!gimmicks.md)
	currently used here just to create custom wiki links - automatic hyperlinking to pages from expressions written in PascalCase
	FWIW I attempted to implement this "right" way be extending the marked.js parser (https://marked.js.org/using_pro#extensions ) 
	but could not get it to work correctly */
	
	function wikiFormat(text) {
		const PascalCase = /\b[A-Z][a-z]+[A-Z][A-Za-z]+\b/g;

		let wikiText = text.replaceAll(
			PascalCase,
			function (match) {
				let displayClass = "wikiWord";
				if (pageIndex.indexOf(match) === -1) {
					displayClass = "newWikiWord";
				}
				return (
					'<a class="' +
					displayClass +
					'" href="#' +
					match +
					'">' +
					reSpace(match) +
					"</a>"
				);
			}
		);

		return wikiText;
	}

	function reSpace(text) {
		return text.replaceAll(/([a-z])([A-Z])/g, "$1 $2");
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

<nav>{@html wikiFormat(marked(TopNav))}</nav>
<section id="container">
	<section id="pageContent">
		<p>Now viewing: {pageTitle}</p>
		{#if hash !== "Index"}
			{@html wikiFormat(marked(value))}
		{:else}
			<ul>
				{#each pageIndex as page}
					<li>{@html wikiFormat(page)}</li>
				{/each}
			</ul>
		{/if}
	</section>
	{#if hash !== "Index"}
		<section id="editor">
			<h2>
				Markdown Editor <button id="saveData" on:click={saveData}
					>Save</button
				>
			</h2>
			<textarea bind:value />
		</section>
	{/if}
</section>
<nav>{@html wikiFormat(marked(Footer))}</nav>

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
