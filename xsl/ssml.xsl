<?xml version="1.0" encoding="UTF-8"?>
<!--
	xmlns="http://www.w3.org/2001/10/synthesis" 
	-->
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	 
	xsi:schemaLocation="http://www.w3.org/2001/10/synthesis	http://www.w3.org/TR/speech-synthesis11/synthesis.xsd" 
	 xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:func="http://exslt.org/functions" extension-element-prefixes="func" xmlns:functx="http://www.functx.com">
	
	<xsl:import href="terms.xsl"></xsl:import>	
	
	<xsl:output method="xml" version="1.0" encoding="UTF-8"	indent="yes"></xsl:output>
	
	<xsl:variable name="lang">
		<xsl:value-of select="/article/meta/lang"></xsl:value-of>
	</xsl:variable>	

	<xsl:template match="loop">
		<xsl:call-template name="contentpages"></xsl:call-template>
	</xsl:template>
	
	<xsl:template name="contentpages">
		<xsl:apply-templates select="article"/>
	</xsl:template>	
	
	<xsl:template match="article">
		<xsl:element name="article">
			<xsl:attribute name="id">
				<xsl:value-of select="@id"></xsl:value-of>
			</xsl:attribute>
			<xsl:element name="speak">
				<xsl:attribute name="voice">
					<xsl:text>2</xsl:text>
				</xsl:attribute>
			
				<xsl:element name="p">
				
					<xsl:value-of select="$word_chapter"/>
					<xsl:text> </xsl:text>
					<xsl:value-of select="@tocnumber"></xsl:value-of>
					
					<xsl:element name="break">
						<xsl:attribute name="strength">
							<xsl:text>medium</xsl:text>
						</xsl:attribute>
					</xsl:element>

					<xsl:value-of select="@toctext"></xsl:value-of>
					
					<xsl:element name="break">
						<xsl:attribute name="time">
							<xsl:text>700ms</xsl:text>
						</xsl:attribute>
					</xsl:element>

				</xsl:element>
						
			</xsl:element>

			<xsl:apply-templates/>
			
		</xsl:element>
	
	</xsl:template>	


	<xsl:template match="loop_objects">
	</xsl:template>
	
	<xsl:template match="link">
	</xsl:template>
	<xsl:template match="php_link">
	</xsl:template>
	<xsl:template match="php_link_image">
	</xsl:template>

	<xsl:template match="extension">
	
		<xsl:choose>
			<xsl:when test="@extension_name='loop_figure'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="@extension_name='loop_formula'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="@extension_name='loop_listing'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="@extension_name='loop_media'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="@extension_name='loop_table'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:when test="@extension_name='loop_task'">
				<xsl:call-template name="loop_object">
                	<xsl:with-param name="object" select="."></xsl:with-param>
				</xsl:call-template>
			</xsl:when>

			<xsl:when test="@extension_name='loop_title'">
				<xsl:apply-templates/>
			</xsl:when>	
			<xsl:when test="@extension_name='loop_description'">
				<xsl:apply-templates/>		
			</xsl:when>	
			<xsl:when test="@extension_name='loop_copyright'">
				<xsl:apply-templates/>
			</xsl:when>	

			
		</xsl:choose>	

		<!--
				<xsl:element name="break">
					<xsl:attribute name="strength">
						<xsl:text>medium</xsl:text>
					</xsl:attribute>
				</xsl:element>
		-->
	</xsl:template>

	<xsl:template name="loop_object">
		<xsl:param name="object"></xsl:param>

		<xsl:variable name="objectid">
			<xsl:choose>
				<xsl:when test="$object[@index='false']"> 
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="@id"></xsl:value-of>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>

		<xsl:element name="p">
			<xsl:text> </xsl:text>
		</xsl:element>
		<xsl:element name="break">
			<xsl:attribute name="time">
				<xsl:text>1200ms</xsl:text>
			</xsl:attribute>
		</xsl:element>
		<xsl:choose>
			<xsl:when test="//*/loop_object[@refid = $objectid]/object_number">
			
				<xsl:element name="p">
					<xsl:choose>
						<xsl:when test="$object[@extension_name='loop_figure']">
							<xsl:value-of select="$phrase_figure_number"></xsl:value-of>
						</xsl:when>
						<xsl:when test="$object[@extension_name='loop_formula']">
							<xsl:value-of select="$phrase_formula_number"></xsl:value-of>
						</xsl:when>
						<xsl:when test="$object[@extension_name='loop_listing']">
							<xsl:value-of select="$phrase_listing_number"></xsl:value-of>
						</xsl:when>
						<xsl:when test="$object[@extension_name='loop_media']">
							<xsl:value-of select="$phrase_media_number"></xsl:value-of>
						</xsl:when>
						<xsl:when test="$object[@extension_name='loop_task']">
							<xsl:value-of select="$phrase_task_number"></xsl:value-of>
						</xsl:when>
						<xsl:when test="$object[@extension_name='loop_table']">
							<xsl:value-of select="$phrase_table_number"></xsl:value-of>
						</xsl:when>
						<xsl:otherwise>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:value-of select="//*/loop_object[@refid = $objectid]/object_number"></xsl:value-of>
				</xsl:element>
				<xsl:text> </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$object[@extension_name='loop_figure']">
						<xsl:value-of select="$phrase_figure"></xsl:value-of>
					</xsl:when>
					<xsl:when test="$object[@extension_name='loop_formula']">
						<xsl:value-of select="$phrase_formula"></xsl:value-of>
					</xsl:when>
					<xsl:when test="$object[@extension_name='loop_listing']">
						<xsl:value-of select="$phrase_listing"></xsl:value-of>
					</xsl:when>
					<xsl:when test="$object[@extension_name='loop_media']">
						<xsl:value-of select="$phrase_media"></xsl:value-of>
					</xsl:when>
					<xsl:when test="$object[@extension_name='loop_task']">
						<xsl:value-of select="$phrase_task"></xsl:value-of>
					</xsl:when>
					<xsl:when test="$object[@extension_name='loop_table']">
						<xsl:value-of select="$phrase_table"></xsl:value-of>
					</xsl:when>
					<xsl:otherwise>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:text> </xsl:text>
			</xsl:otherwise>
		</xsl:choose>	


		<xsl:choose>
			<xsl:when test="$object/descendant::extension[@extension_name='loop_title']">
				<xsl:apply-templates select="$object/descendant::extension[@extension_name='loop_title']" mode="loop_object"></xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$object/@title"></xsl:value-of>	
			</xsl:otherwise>
		</xsl:choose>
		<xsl:if test="($object/@description) or ($object/descendant::extension[@extension_name='loop_description'])">
			<xsl:element name="break">
				<xsl:attribute name="strength">
					<xsl:text>medium</xsl:text>
				</xsl:attribute>
			</xsl:element>
			<xsl:choose>
				<xsl:when test="$object/descendant::extension[@extension_name='loop_description']">
					<xsl:apply-templates select="$object/descendant::extension[@extension_name='loop_description']" mode="loop_object"></xsl:apply-templates>
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="$object/@description"></xsl:value-of>	
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="$object/descendant::extension[@extension_name='loop_copyright']">
				<xsl:element name="break">
					<xsl:attribute name="strength">
						<xsl:text>medium</xsl:text>
					</xsl:attribute>
				</xsl:element>
				<xsl:apply-templates select="$object/descendant::extension[@extension_name='loop_copyright']" mode="loop_object"></xsl:apply-templates>
			</xsl:when>
			<xsl:otherwise>
				<xsl:element name="break">
					<xsl:attribute name="strength">
						<xsl:text>medium</xsl:text>
					</xsl:attribute>
				</xsl:element>
				<xsl:value-of select="$object/@copyright"></xsl:value-of>	
			</xsl:otherwise>
		</xsl:choose>

		<xsl:element name="break">
			<xsl:attribute name="strength">
				<xsl:text>medium</xsl:text>
			</xsl:attribute>
		</xsl:element>
	</xsl:template>

	<xsl:template match="heading">
	
		<xsl:element name="speak">
			<xsl:attribute name="voice">
				<xsl:text>2</xsl:text>
			</xsl:attribute>
			<!--<xsl:element name="amazon:autobreaths">-->
			<xsl:apply-templates/>
			<!--</xsl:element>-->
		</xsl:element>
		
		<xsl:element name="break">
			<xsl:attribute name="time">
				<xsl:text>1200ms</xsl:text>
			</xsl:attribute>
		</xsl:element>
		
	</xsl:template>
	
	
	<xsl:template match="paragraph">
		<xsl:choose>
			<xsl:when test="ancestor::paragraph">
				<xsl:element name="replace_speak">
					<xsl:attribute name="voice">
						<xsl:value-of select="functx:select_voice()"/>
					</xsl:attribute>
					<xsl:apply-templates/>
				</xsl:element>
				<xsl:element name="replace_speak_next">
					<xsl:attribute name="voice">
						<xsl:value-of select="functx:select_voice()"/>
					</xsl:attribute>
				</xsl:element>
			</xsl:when>
			<xsl:otherwise>
				<xsl:element name="speak">
					<xsl:attribute name="voice">
						<xsl:value-of select="functx:select_voice()"/>
					</xsl:attribute>
					<xsl:apply-templates/>
					
					<xsl:element name="break">
						<xsl:attribute name="strength">
							<xsl:text>strong</xsl:text>
						</xsl:attribute>
					</xsl:element>

				</xsl:element>
				

			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<func:function name="functx:select_voice">
	
		<xsl:choose>
			<xsl:when test="extension[@extension_name='loop_figure']">
        		<func:result>2</func:result>
			</xsl:when>
			<xsl:when test="extension[@extension_name='loop_formula']">
        		<func:result>2</func:result>
			</xsl:when>
			<xsl:when test="extension[@extension_name='loop_listing']">
        		<func:result>2</func:result>
			</xsl:when>
			<xsl:when test="extension[@extension_name='loop_media']">
        		<func:result>2</func:result>
			</xsl:when>
			<xsl:when test="extension[@extension_name='loop_table']">
        		<func:result>2</func:result>
			</xsl:when>
			<xsl:when test="extension[@extension_name='loop_task']">
        		<func:result>2</func:result>
			</xsl:when>

			<xsl:otherwise>
        		<func:result>1</func:result>
			</xsl:otherwise>

		</xsl:choose>
		

	</func:function>
		
	<xsl:template match="preblock">
		
	</xsl:template>

	
	<xsl:template match="space">
		<!--<xsl:element name="break">
			<xsl:attribute name="time">
				<xsl:text>1200ms</xsl:text>
			</xsl:attribute>
		</xsl:element>-->
	</xsl:template>		

	<xsl:template match="meta">

	</xsl:template>		
	
</xsl:stylesheet>