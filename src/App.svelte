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
	let pageIndex = Array();

	$: pageTitle = reSpace(hash);

	let value = `Please wait while content loads...`;

	async function getPageIndex() {
		const response = await fetch($config.ajaxURL, {
			method: "post",
			headers: {
				Accept: "application/json, text/plain, /",
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ action: "getIndex" }),
		})
			.then((response) => response.json())
			.then((data) => {
				pageIndex = data;
				console.log(pageIndex);
			});
	}
	getPageIndex();

	/* my new generic function to grab text from the API */
	const getTextFromAPI = async (action, id) => {
		const response = await fetch($config.ajaxURL, {
			method: "post",
			headers: {
				Accept: "application/json, text/plain, /",
				"Content-Type": "application/json",
			},
			body: JSON.stringify({ action: action, id: id }),
		});
		const text = await response.text();
		return text;
	};

	/* grab the topnav */
	getTextFromAPI("load", "TopNav").then(function (result) {
		TopNav = result;
	});
	/* grab the main page body */
	getTextFromAPI("load", hash).then(function (result) {
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

	function wikiFormat(text) {
		const PascalCase = /[A-Z][a-z]+[A-Z][A-Za-z]+/g;

		let wikiText = text.replaceAll(PascalCase, function (m) {
			let displayClass = "wikiWord";
			if (pageIndex.indexOf(m) === -1) {
				displayClass = "newWikiWord";
			}
			return (
				'<a class="' +
				displayClass +
				'" href="#' +
				m +
				'">' +
				reSpace(m) +
				"</a>"
			);
		});

		return wikiText;
	}

	function reSpace(text) {
		return text.replaceAll(/([a-z])([A-Z])/g, "$1 $2");
	}

	window.addEventListener("hashchange", function () {
		hash = getHash();
		getPageContentFromFilesystem();
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
		<nav>{@html wikiFormat(marked(TopNav))}</nav>
		<p>Now viewing: {pageTitle}</p>
		{@html wikiFormat(marked(value))}
	</section>
	<section id="editor">
		<h2>
			Markdown Editor <button id="saveData" on:click={saveData}
				>Save</button
			>
		</h2>
		<textarea bind:value />
	</section>
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
