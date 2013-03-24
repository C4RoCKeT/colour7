<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <style>
            html, body { height:100%;width:100%;margin:0;padding:0; }
            body { padding-top:80px;box-sizing: border-box; }
            canvas { border:10px solid #EEEEEE; }
        </style>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <script>
            var width = 0;
            var height = 0;
            var gridWidth = 7;
            var gridHeight = 7;
            var grid = new Array(gridWidth);
            var gridBorderWidth = 10;
            var gridColour = "EEEEEE"
            var canvas;
            var xOffset = gridBorderWidth/2;
            var yOffset = gridBorderWidth/2;
            var borderTotalWidth = gridWidth * gridBorderWidth;
            var borderTotalHeight = gridHeight * gridBorderWidth;
            var blockWidth,blockHeight,extraBlockWidth,extraBlockHeight;
            var colours = ["007BBB","2F847C","FFB347","C23B22","966FD6"];
            var free = [];
            var xMultiplier,yMultiplier;
            var prevX,prevY;
            var skipRandom = false;
            $(function(){
                canvas = document.getElementById('canvas');
                var canvasWrapStyle = getComputedStyle(document.getElementById('canvas-wrap'));
                var ctx = canvas.getContext('2d');
                width = canvas.width = parseInt(canvasWrapStyle.getPropertyValue('width'));
                height = canvas.height = parseInt(canvasWrapStyle.getPropertyValue('height'));
                blockWidth = (width - borderTotalWidth) / gridWidth;
                blockHeight = (height - borderTotalHeight) / gridHeight;
                for(var i = 0; i< grid.length;i++) {
                    grid[i] = new Array(gridHeight);
                }
                drawGrid(ctx);
                $(canvas).click(function(e){
                    xPos = Math.ceil((e.pageX - this.offsetLeft) / (blockWidth + extraBlockWidth))-1;
                    yPos = Math.ceil((e.pageY - this.offsetTop) / (blockHeight + extraBlockHeight))-1;
                    if(prevX != xPos || prevY != yPos) {
                        if(prevX != undefined && prevY != undefined) {
                            clearSelected(ctx,grid[prevX][prevY]);
                            if(moveBlock(ctx,prevX,prevY,xPos,yPos)){
                                prevX = prevY = undefined;
                                if(!skipRandom) {
                                    for(var i = 0; i< 3;i++) {
                                        if(!placeRandomBlock(ctx)) {
                                            alert('Game Over!');
                                            break;
                                        }
                                    }
                                } else {
                                    skipRandom = false;
                                }
                            } else {
                                prevX = xPos;
                                prevY = yPos;
                                selectBlock(ctx, grid[xPos][yPos]);
                            }
                        } else if(grid[xPos][yPos] != undefined) {
                            prevX = xPos;
                            prevY = yPos;
                            selectBlock(ctx, grid[xPos][yPos]);
                        }
                    } else {
                        clearSelected(ctx,grid[prevX][prevY]);
                        prevX = prevY = undefined;
                    }
                    
                });
                
                for(var i = 0; i < gridWidth; i++) {
                    for(var j = 0; j < gridHeight; j++) {
                        if(grid[i][j] == undefined) {
                            free.push(i+""+j);
                        }
                    }
                }
                for(var i = 0; i< 3;i++) {
                    if(!placeRandomBlock(ctx))
                        break;
                }
            });
            
            function checkAdjacentColour(ctx,block) {
                var top,right,bottom,left = undefined;
                if(block.yPos-1  >= 0)
                    var top = grid[block.xPos][block.yPos-1];
                if(block.yPos-1  >= 0 && block.xPos+1 < gridWidth)
                    var topRight = grid[block.xPos+1][block.yPos-1];
                if(block.xPos+1 < gridWidth)
                    var right = grid[block.xPos+1][block.yPos];
                if(block.yPos+1 < gridHeight && block.xPos+1 < gridWidth)
                    var bottomRight = grid[block.xPos+1][block.yPos+1];
                if(block.yPos+1 < gridHeight)
                    var bottom = grid[block.xPos][block.yPos+1];
                if(block.yPos+1 < gridHeight && block.xPos-1 >= 0)
                    var bottomLeft = grid[block.xPos-1][block.yPos+1];
                if(block.xPos-1 >= 0)
                    var left = grid[block.xPos-1][block.yPos];
                if(block.yPos-1  >= 0 && block.xPos-1 >= 0)
                    var topLeft = grid[block.xPos-1][block.yPos-1];
                var horizontalBlocks = [block];
                var verticalBlocks = [block];
                var topDiagonalBlocks = [block];
                var bottomDiagonalBlocks = [block];
                if(top != undefined && top.colour == block.colour) {
                    verticalBlocks = verticalBlocks.concat(checkDirectionColour(top,'top',[]));
                }
                if(topRight != undefined && topRight.colour == block.colour) {
                    bottomDiagonalBlocks = bottomDiagonalBlocks.concat(checkDirectionColour(topRight,'topRight',[]));
                }
                if(right != undefined && right.colour == block.colour) {
                    horizontalBlocks = horizontalBlocks.concat(checkDirectionColour(right,'right',[]));
                }
                if(bottomRight != undefined && bottomRight.colour == block.colour) {
                    topDiagonalBlocks = topDiagonalBlocks.concat(checkDirectionColour(bottomRight,'bottomRight',[]));
                }
                if(bottom != undefined && bottom.colour == block.colour) {
                    verticalBlocks = verticalBlocks.concat(checkDirectionColour(bottom,'bottom',[]));
                }
                if(bottomLeft != undefined && bottomLeft.colour == block.colour) {
                    bottomDiagonalBlocks = bottomDiagonalBlocks.concat(checkDirectionColour(bottomLeft,'bottomLeft',[]));
                }
                if(left != undefined && left.colour == block.colour) {
                    horizontalBlocks = horizontalBlocks.concat(checkDirectionColour(left,'left',[]));
                }
                if(topLeft != undefined && topLeft.colour == block.colour) {
                    topDiagonalBlocks = topDiagonalBlocks.concat(checkDirectionColour(topLeft,'topLeft',[]));
                }
                if(horizontalBlocks.length >= 4)
                    clearBlocks(ctx,horizontalBlocks);
                if(verticalBlocks.length >= 4)
                    clearBlocks(ctx,verticalBlocks);
                if(topDiagonalBlocks.length >= 4)
                    clearBlocks(ctx,topDiagonalBlocks);
                if(bottomDiagonalBlocks.length >= 4)
                    clearBlocks(ctx,bottomDiagonalBlocks);
            }
            
            function clearBlocks(ctx,blocks) {
                skipRandom = true;
                for(var block in blocks) {
                    clearBlock(ctx,blocks[block]);
                }
            }
            
            function checkDirectionColour(block,direction,prevBlocks) {
                prevBlocks.push(block);
                var nextBlock = undefined;
                switch(direction) {
                    case 'top':
                        if(block.yPos-1 >= 0)
                            var nextBlock = grid[block.xPos][block.yPos-1];
                        break;
                    case 'topRight':
                        if(block.yPos-1 >= 0 && block.xPos+1 < gridWidth)
                            var nextBlock = grid[block.xPos+1][block.yPos-1];
                        break;
                    case 'right':
                        if(block.xPos+1 < gridWidth)
                            var nextBlock = grid[block.xPos+1][block.yPos];
                        break;
                    case 'bottomRight':
                        if(block.yPos+1  < gridHeight && block.xPos+1 < gridWidth)
                            var nextBlock = grid[block.xPos+1][block.yPos+1];
                        break;
                    case 'bottom':
                        if(block.yPos+1  < gridHeight)
                            var nextBlock = grid[block.xPos][block.yPos+1];
                        break;
                    case 'bottomLeft':
                        if(block.yPos+1  < gridHeight && block.xPos-1 >= 0)
                            var nextBlock = grid[block.xPos-1][block.yPos+1];
                        break;
                    case 'left':
                        if(block.xPos-1 >= 0)
                            var nextBlock = grid[block.xPos-1][block.yPos];
                        break;
                    case 'topLeft':
                        if(block.yPos-1 >= 0 && block.xPos-1 >= 0)
                            var nextBlock = grid[block.xPos-1][block.yPos-1];
                        break;
                }
                if(nextBlock != undefined) {
                    if(nextBlock.colour == block.colour) {
                        prevBlocks = checkDirectionColour(nextBlock,direction,prevBlocks);
                    }
                }
                return prevBlocks
            }
        
            function placeRandomBlock(ctx) {
                var freeIndex = (Math.floor(Math.random() * free.length));
                var coordinates = free[freeIndex];
                if(coordinates != undefined) {
                    var xPos = Math.floor(coordinates/10);
                    var yPos = coordinates - (xPos*10);
                    if(placeBlock(ctx,xPos,yPos,colours[(Math.floor(Math.random() * colours.length))])) {
                        free.splice(freeIndex,1);
                        return true;
                    }
                    return false;
                } else {
                    return false;
                }
            }
            
            function selectBlock(ctx,block) {
                ctx.fillStyle = '#' + block.colour;
                ctx.strokeStyle= '#' + block.colour;
                ctx.beginPath();
                ctx.rect(block.xPos*xMultiplier,block.yPos*yMultiplier,blockWidth,blockHeight);
                ctx.fill();
                ctx.stroke();
                return true;
            }
            
            function clearSelected(ctx,block) {
                var blockColour = block.colour;
                block.colour = 'FFFFFF';
                selectBlock(ctx,block);
                drawGrid(ctx);
                placeBlock(ctx,block.xPos,block.yPos,blockColour);
            }
            
            function placeBlock(ctx,xPos,yPos,colour) {
                var block = grid[xPos][yPos] = {
                    xPos:xPos,
                    yPos:yPos,
                    colour:colour
                }
                ctx.fillStyle = '#' + block.colour;
                ctx.beginPath();
                ctx.rect(xPos*xMultiplier,yPos*yMultiplier,blockWidth,blockHeight);
                ctx.fill();
                checkAdjacentColour(ctx,block);
                return true;
            }
        
            function moveBlock(ctx,oldX,oldY,newX,newY) {
                var newBlock = grid[newX][newY];
                if(newBlock == undefined) {
                    var oldBlock = grid[oldX][oldY];
                    clearBlock(ctx,oldBlock);
                    if(placeBlock(ctx,newX,newY,oldBlock.colour)) {
                        free.splice(free.indexOf(newX+""+newY),1);
                        return true;
                    }
                    return false;
                } else {
                    return false;
                }
            }
            
            function clearBlock(ctx,block) {
                free.push(block.xPos+""+block.yPos);
                grid[block.xPos][block.yPos] = undefined;
                ctx.fillStyle = '#FFFFFF';
                ctx.beginPath();
                ctx.rect(block.xPos*xMultiplier,block.yPos*yMultiplier,blockWidth,blockHeight);
                ctx.fill();
            }
            
            function drawGrid(ctx) {
                ctx.lineWidth = gridBorderWidth;
                ctx.strokeStyle=gridColour;
                ctx.beginPath();
                extraBlockWidth = borderTotalWidth / (gridWidth -1);
                extraBlockHeight = borderTotalHeight / (gridHeight -1);
                xMultiplier = ((width - borderTotalWidth) / gridWidth) + extraBlockWidth;
                yMultiplier = ((height - borderTotalHeight) / gridHeight) + extraBlockHeight;
                for(var i = blockWidth + (extraBlockWidth / 2); i < width; i += blockWidth + extraBlockWidth) {
                    ctx.moveTo(i,0);
                    ctx.lineTo(i,height);
                }
                for(var i = blockHeight + (extraBlockWidth / 2); i < height; i += blockHeight + extraBlockWidth) {
                    ctx.moveTo(0,i);
                    ctx.lineTo(width,i);
                }
                ctx.stroke();
            }
        </script>
    </head>
    <body>
        <div id="canvas-wrap" style="margin-left:auto;margin-right:auto;width:700px; height:700px;">
            <canvas id="canvas"></canvas>
        </div>
    </body>
</html>
