{extends file="layout.tpl"}

{block name="title"}Categories{/block}

{block name="content"}
    <h1>Categories</h1>
    <p><a href="/articles">Articles</a></p>

    {if !$categories}
        <p>No categories yet. Load demo data: <code>php bin/console db:seed</code>.</p>
    {else}
        <table>
            <thead>
            <tr><th>ID</th><th>Name</th><th>Description</th></tr>
            </thead>
            <tbody>
            {foreach $categories as $category}
                <tr>
                    <td>{$category->id}</td>
                    <td>{$category->name}</td>
                    <td>{$category->description}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    {/if}
{/block}
